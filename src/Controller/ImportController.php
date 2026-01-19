<?php

namespace App\Controller;

use App\Entity\ImportJob;
use App\Message\ImportJobMessage;
use App\Repository\ImportJobRepository;
use App\Service\ImportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

#[Route('/api/import', name: 'api_import_')]
class ImportController extends AbstractController
{
    public function __construct(
        private ImportService $importService,
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $bus,
        private ImportJobRepository $importJobRepository
    ) {
    }

    #[Route('/headers', name: 'headers', methods: ['POST'])]
    #[OA\Post(
        summary: "Reads the first line of an uploaded CSV to return headers.",
        tags: ["Import"]
    )]
    #[OA\RequestBody(
        content: [
            new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "file", type: "string", format: "binary"),
                        new OA\Property(property: "delimiter", type: "string", example: ";")
                    ]
                )
            )
        ]
    )]
    public function getHeaders(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        $delimiter = $request->request->get('delimiter', ';');

        if (!$file) {
            return new JsonResponse(['error' => 'No file uploaded or file too large'], 400);
        }

        if (!$file->isValid()) {
            return new JsonResponse(['error' => 'File upload error: ' . $file->getErrorMessage()], 400);
        }

        $handle = fopen($file->getPathname(), 'r');
        if ($handle === false) {
            return new JsonResponse(['error' => 'Could not read file'], 500);
        }

        $line = fgets($handle);
        fclose($handle);

        // Remove BOM if present
        $line = preg_replace('/^\xEF\xBB\xBF/', '', $line);

        $headers = [];
        if ($line) {
            $headers = str_getcsv($line, $delimiter);
        }

        return new JsonResponse([
            'headers' => $headers
        ]);
    }

    #[Route('/validate', name: 'validate', methods: ['POST'])]
    #[OA\Post(
        summary: "Validates the import mapping and column requirements.",
        tags: ["Import"]
    )]
    public function validate(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $mapping = $data['mapping'] ?? [];

        $errors = [];
        if (!in_array('code', $mapping)) {
            $errors[] = "Column 'code' (Article Code) must be mapped.";
        }

        return new JsonResponse([
            'valid' => empty($errors),
            'errors' => $errors
        ]);
    }

    #[Route('/run', name: 'run', methods: ['POST'])]
    #[OA\Post(
        summary: "Starts an import job.",
        tags: ["Import"]
    )]
    #[OA\RequestBody(
        content: [
            new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "file", type: "string", format: "binary"),
                        new OA\Property(property: "mapping", type: "string", description: "JSON string of mapping"),
                        new OA\Property(property: "delimiter", type: "string", example: ";"),
                        new OA\Property(property: "type", type: "string", enum: ["articles", "cars"], default: "articles"),
                        new OA\Property(property: "article_identifier_field", type: "string", description: "For car import: article identifier field in mapping", default: "code"),
                        new OA\Property(property: "debug_delay", type: "integer", description: "Debug: delay in milliseconds between batches (for testing progress tracking)", example: 1000)
                    ]
                )
            )
        ]
    )]
    public function runImport(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        $delimiter = $request->request->get('delimiter', ';');
        $mappingJson = $request->request->get('mapping');
        $mapping = json_decode($mappingJson, true);
        $type = $request->request->get('type', 'articles');
        $articleIdentifierField = $request->request->get('article_identifier_field', 'code');
        $debugDelay = $request->request->get('debug_delay');
        $debugDelay = 30;

        if (!$file || !$mapping) {
            return new JsonResponse(['error' => 'Missing file or mapping'], 400);
        }

        // Move file
        $uploadDir = $this->getParameter('kernel.project_dir') . '/var/uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $filename = uniqid('import_') . '.csv';
        $file->move($uploadDir, $filename);
        $filePath = $uploadDir . '/' . $filename;

        // Create Job
        $job = new ImportJob();
        $job->setFilePath($filePath);
        $job->setMapping($mapping);
        $job->setImportType($type);
        if ($type === 'cars') {
            $job->setArticleIdentifierField($articleIdentifierField);
        }
        if ($debugDelay !== null && is_numeric($debugDelay)) {
            $job->setDebugDelay((int) $debugDelay);
        }

        // Initialize totalRows as null, it will be calculated in the worker
        $job->setTotalRows(null);

        $this->entityManager->persist($job);
        $this->entityManager->flush();

        // Dispatch
        $this->bus->dispatch(new ImportJobMessage($job->getId()));

        // Fast response
        return new JsonResponse([
            'jobId' => $job->getId(),
            'status' => 'queued',
            'progressUrl' => '/api/import/status/' . $job->getId()
        ]);
    }

    #[Route('/jobs', name: 'list_jobs', methods: ['GET'])]
    #[OA\Get(
        summary: "Get list of all import jobs.",
        tags: ["Import"]
    )]
    #[OA\Parameter(
        name: "status",
        in: "query",
        description: "Filter jobs by status (queued, processing, completed, cancelled, etc.)",
        required: false,
        schema: new OA\Schema(type: "string")
    )]
    #[OA\Parameter(
        name: "limit",
        in: "query",
        description: "Maximum number of jobs to return (default: 100, max: 500)",
        required: false,
        schema: new OA\Schema(type: "integer", default: 100)
    )]
    public function listJobs(Request $request): JsonResponse
    {
        $status = $request->query->get('status');
        $limit = min((int) $request->query->get('limit', 100), 500);

        $jobs = $this->importJobRepository->findAllOrderedByDate($status, $limit);

        $result = array_map(function (ImportJob $job) {
            return [
                'id' => $job->getId(),
                'status' => $job->getStatus(),
                'importType' => $job->getImportType(),
                'processedRows' => $job->getProcessedRows(),
                'totalRows' => $job->getTotalRows(),
                'progress' => $job->getTotalRows() > 0
                    ? round(($job->getProcessedRows() / $job->getTotalRows()) * 100, 2)
                    : 0,
                'createdAt' => $job->getCreatedAt()->format('c')
            ];
        }, $jobs);

        return new JsonResponse([
            'jobs' => $result,
            'count' => count($result)
        ]);
    }

    #[Route('/status/{id}', name: 'status', methods: ['GET'])]
    #[OA\Get(
        summary: "Get import job status.",
        tags: ["Import"]
    )]
    public function status(int $id): JsonResponse
    {
        $job = $this->importJobRepository->find($id);

        if (!$job) {
            return new JsonResponse(['error' => 'Job not found'], 404);
        }

        return new JsonResponse([
            'id' => $job->getId(),
            'status' => $job->getStatus(),
            'processedRows' => $job->getProcessedRows(),
            'totalRows' => $job->getTotalRows(),
            'createdAt' => $job->getCreatedAt()->format('c')
        ]);
    }

    #[Route('/pause/{id}', name: 'pause', methods: ['POST'])]
    #[OA\Post(
        summary: "Pause an import job.",
        tags: ["Import"]
    )]
    public function pause(int $id): JsonResponse
    {
        $job = $this->importJobRepository->find($id);
        if (!$job) {
            return new JsonResponse(['error' => 'Job not found'], 404);
        }

        $job->setStatus('cancelling'); // Worker will see this and stop, setting it to 'cancelled' (or 'paused' if we implement pause logic)
        // With current implementation 'cancelled' stops loop. 
        // We might want 'pausing' -> 'paused'.
        // Let's use 'cancelling' -> 'cancelled' as "Stop".
        // If we want Resume, we need "paused".
        // Let's change existing logic:

        // Actually, to resume, we just need the offset.
        // If status is 'cancelled', we can resume it.
        // So 'pause' is effectively 'stop worker'.

        $this->entityManager->flush();

        return new JsonResponse(['status' => 'cancelling']);
    }

    #[Route('/resume/{id}', name: 'resume', methods: ['POST'])]
    #[OA\Post(
        summary: "Resume a cancelled/paused import job.",
        tags: ["Import"]
    )]
    public function resume(int $id): JsonResponse
    {
        $job = $this->importJobRepository->find($id);
        if (!$job) {
            return new JsonResponse(['error' => 'Job not found'], 404);
        }

        // If job is already processing, ignore
        if ($job->getStatus() === 'processing') {
            return new JsonResponse(['status' => 'processing', 'message' => 'Job already running']);
        }

        $job->setStatus('queued'); // or just dispatch
        $this->entityManager->flush();

        $this->bus->dispatch(new ImportJobMessage($job->getId()));

        return new JsonResponse(['status' => 'queued']);
    }
}
