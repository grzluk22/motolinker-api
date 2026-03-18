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
//@TODO: czy ta funkcja odrazu nie powinna zwracać informacji że istnieje już importMapping z takimi samymi nagłówkami?
    #[Route('/headers', name: 'headers', methods: ['POST'])]
    #[OA\Post(
        summary: "Odczytuje pierwszą linię przesłanego pliku CSV i zwraca nagłówki.",
        description: "Odczytuje pierwszą linię przesłanego pliku CSV i zwraca nagłówki. Zwraca również tymczasową nazwę pliku, która będzie potrzebna w kolejnym kroku importu, tak aby użytkownik nie musiał ponownie przesyłać pliku.",
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
            return new JsonResponse(['error' => 'Nie przekazano żadnego pliku lub plik jest zbyt duży.'], 400);
        }

        if (!$file->isValid()) {
            return new JsonResponse(['error' => 'Wystąpił błąd podczas przesyłania pliku: ' . $file->getErrorMessage()], 400);  
        }

        $handle = fopen($file->getPathname(), 'r');
        if ($handle === false) {
            return new JsonResponse(['error' => 'Nie udało się odczytać pliku.'], 500);
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
        summary: "Waliduje mapowanie importu i wymagane kolumny.",
        tags: ["Import"]
    )]
    public function validate(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $mapping = $data['mapping'] ?? [];

        $errors = [];
        if (!in_array('code', $mapping)) {
            $errors[] = "Kolumna 'code' (Kod artykułu) musi być zmapowana.";
        }

        return new JsonResponse([
            'valid' => empty($errors),
            'errors' => $errors
        ]);
    }

    #[Route('/run', name: 'run', methods: ['POST'])]
    #[OA\Post(
        summary: "Uruchamia import.",
        tags: ["Import"]
    )]
    #[OA\RequestBody(
        content: [
            new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "file", type: "string", format: "binary"),
                        new OA\Property(property: "mapping", type: "string", description: "JSON string z mapowaniem"),
                        new OA\Property(property: "delimiter", type: "string", example: ";"),
                        new OA\Property(property: "type", type: "string", enum: ["articles", "cars", "categories"], default: "articles"),
                        new OA\Property(property: "article_identifier_field", type: "string", description: "Dla importu samochodów: pole identyfikatora artykułu w mapowaniu", default: "code"),
                        new OA\Property(property: "debug_delay", type: "integer", description: "Debug: opóźnienie w milisekundach między partiami (do testowania śledzenia postępu)", example: 1000)
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

        if (!$file || !$mapping) {
            return new JsonResponse(['error' => 'Brak pliku lub mapowania'], 400);
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
        $user = $this->getUser();
        if ($user) {
            $job->setUser($user);
        }
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
        summary: "Pobiera listę wszystkich importów.",
        tags: ["Import"]
    )]
    #[OA\Parameter(
        name: "status",
        in: "query",
        description: "Filtruje importy wg statusu (queued, processing, completed, cancelled, etc.)",
        required: false,
        schema: new OA\Schema(type: "string")
    )]
    #[OA\Parameter(
        name: "limit",
        in: "query",
        description: "Maksymalna liczba importów do zwrócenia (domyślnie: 100, max: 500)",
        required: false,
        schema: new OA\Schema(type: "integer", default: 100)
    )]
    public function listJobs(Request $request): JsonResponse
    {
        $queryParams = $request->query->all();
        
        $criteriaParam = $queryParams['criteria'] ?? null;
        $criteria = is_string($criteriaParam) ? json_decode($criteriaParam, true) : $criteriaParam;
        if (!is_array($criteria)) {
            $criteria = [];
        }
        
        // Backward compatibility for status
        $status = $request->query->get('status');
        if (!empty($status)) {
            $criteria['status'] = $status;
        }

        $limit = min((int) $request->query->get('limit', 100), 500);
        $offset = (int) $request->query->get('offset', 0);
        
        $orderByParam = $queryParams['orderBy'] ?? null;
        $orderBy = is_string($orderByParam) ? json_decode($orderByParam, true) : $orderByParam;
        if (!is_array($orderBy) || empty($orderBy)) {
            $orderBy = ['createdAt' => 'DESC'];
        }

        $jobs = $this->importJobRepository->findWithFilters($criteria, $orderBy, $limit, $offset);

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
                'createdAt' => $job->getCreatedAt()->format('c'),
                'errorMessage' => $job->getErrorMessage(),
            ];
        }, $jobs);

        return new JsonResponse([
            'jobs' => $result,
            'count' => count($result)
        ]);
    }

    #[Route('/status/{id}', name: 'status', methods: ['GET'])]
    #[OA\Get(
        summary: "Pobiera status importu.",
        tags: ["Import"]
    )]
    public function status(int $id): JsonResponse
    {
        $job = $this->importJobRepository->find($id);

        if (!$job) {
            return new JsonResponse(['error' => 'Nie znaleziono importu'], 404);
        }

        return new JsonResponse([
            'id' => $job->getId(),
            'status' => $job->getStatus(),
            'processedRows' => $job->getProcessedRows(),
            'totalRows' => $job->getTotalRows(),
            'createdAt' => $job->getCreatedAt()->format('c'),
            'errorMessage' => $job->getErrorMessage(),
        ]);
    }

    #[Route('/pause/{id}', name: 'pause', methods: ['POST'])]
    #[OA\Post(
        summary: "Wstrzymuje import.",
        tags: ["Import"]
    )]
    public function pause(int $id): JsonResponse
    {
        $job = $this->importJobRepository->find($id);
        if (!$job) {
            return new JsonResponse(['error' => 'Job not found'], 404);
        }

        if ($job->getStatus() === ImportJob::STATUS_PROCESSING) {
            $job->setStatus(ImportJob::STATUS_PAUSING);
            $this->entityManager->flush();
        }

        return new JsonResponse(['status' => $job->getStatus()]);
    }

    #[Route('/resume/{id}', name: 'resume', methods: ['POST'])]
    #[OA\Post(
        summary: "Wznawia import.",
        tags: ["Import"]
    )]
    public function resume(int $id): JsonResponse
    {
        $job = $this->importJobRepository->find($id);
        if (!$job) {
            return new JsonResponse(['error' => 'Nie znaleziono importu'], 404);
        }

        // If job is already processing, ignore
        if ($job->getStatus() === ImportJob::STATUS_PROCESSING) {
            return new JsonResponse(['status' => ImportJob::STATUS_PROCESSING, 'message' => 'Job already running']);
        }

        // Resume allowed from paused, cancelled or failed?
        // User said: "Cancelled to zadanie importu ktorego juz nie da się wznowić."
        // So we only resume from paused (and maybe created/failed if we want).
        if ($job->getStatus() === ImportJob::STATUS_CANCELLED) {
            return new JsonResponse(['error' => 'Nie można wznowić anulowanego importu'], 400);
        }

        $job->setStatus(ImportJob::STATUS_QUEUED);
        $this->entityManager->flush();

        $this->bus->dispatch(new ImportJobMessage($job->getId()));

        return new JsonResponse(['status' => ImportJob::STATUS_QUEUED]);
    }

    #[Route('/revert/{id}', name: 'revert', methods: ['DELETE'])]
    #[OA\Delete(
        summary: "Cofnięcie importu (usuwa utworzone rekordy).",
        tags: ["Import"]
    )]
    public function revert(int $id): JsonResponse
    {
        $job = $this->importJobRepository->find($id);
        if (!$job) {
            return new JsonResponse(['error' => 'Nie znaleziono importu'], 404);
        }

        // Only completed or failed jobs should be reverted? 
        // Or maybe even paused?
        if (in_array($job->getStatus(), [ImportJob::STATUS_PROCESSING, ImportJob::STATUS_PAUSING, ImportJob::STATUS_REVERTING])) {
            return new JsonResponse(['error' => 'Import jest w trakcie i nie można go jeszcze cofnąć'], 400);
        }

        try {
            $this->importService->revertJob($id);
            return new JsonResponse(['status' => ImportJob::STATUS_REVERTED]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/jobs/{id}/errors', name: 'job_errors', methods: ['GET'])]
    #[OA\Get(
        summary: "Pobiera błędy zadania importu.",
        description: "Zwraca errorMessage zadania jako ustrukturyzowaną listę. Jeśli zadanie się powiodło, errors będzie pustą tablicą.",
        tags: ["Import"]
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Lista błędów zadania',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'jobId', type: 'integer', example: 88),
                new OA\Property(property: 'status', type: 'string', example: 'failed'),
                new OA\Property(
                    property: 'errors',
                    type: 'array',
                    items: new OA\Items(
                        properties: [new OA\Property(property: 'message', type: 'string')],
                        type: 'object'
                    )
                )
            ],
            type: 'object'
        )
    )]
    #[OA\Response(response: 404, description: 'Nie znaleziono zadania')]
    public function errors(int $id): JsonResponse
    {
        $job = $this->importJobRepository->find($id);

        if (!$job) {
            return new JsonResponse(['error' => 'Nie znaleziono importu'], 404);
        }

        $errors = [];
        if ($job->getErrorMessage() !== null) {
            $errors[] = ['message' => $job->getErrorMessage()];
        }

        return new JsonResponse([
            'jobId'  => $job->getId(),
            'status' => $job->getStatus(),
            'errors' => $errors,
        ]);
    }
}
