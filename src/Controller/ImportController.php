<?php

namespace App\Controller;

use App\Service\ImportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

#[Route('/api/import', name: 'api_import_')]
class ImportController extends AbstractController
{
    public function __construct(
        private ImportService $importService
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
            return new JsonResponse(['error' => 'No file uploaded'], 400);
        }

        $content = file_get_contents($file->getPathname());
        // Read just the first line effectively for headers, but service might process whole content for consistency checks if needed.
        // For efficiency, maybe just read first line here? 
        // Service::getCsvHeaders handles it.

        $headers = $this->importService->getCsvHeaders($content, $delimiter);

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

        // Basic validation: check if 'code' is mapped (assuming it's required for ID)
        // Check if car identifying columns are present IF 'zastosowania' is NOT mapped? 
        // Actually, 'zastosowania' is the Special Column.

        $errors = [];
        if (!in_array('code', $mapping)) {
            $errors[] = "Column 'code' (Article Code) must be mapped.";
        }

        // If "zastosowania" is mapped, we warn or check if we can parse it?
        // We can't validate the content of the file here easily without re-uploading, 
        // but typically this endpoint accepts the mapping solely.

        return new JsonResponse([
            'valid' => empty($errors),
            'errors' => $errors
        ]);
    }

    #[Route('/run', name: 'run', methods: ['POST'])]
    #[OA\Post(
        summary: "Executes the import process.",
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
                        new OA\Property(property: "delimiter", type: "string", example: ";")
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

        if (!$file || !$mapping) {
            return new JsonResponse(['error' => 'Missing file or mapping'], 400);
        }

        $content = file_get_contents($file->getPathname());
        $parsed = $this->importService->parseCsvContent($content, $delimiter);

        // $parsed['data'] has associative arrays with keys from Header based on parseCsvContent logic
        // But parseCsvContent uses the FILE header.
        // The MAPPING maps "CSV Header Name" => "Entity Field Name".

        // We need to transform the parsed data according to mapping.
        // processImport expects data where keys might be CSV headers, and it uses mapping to find values.

        $stats = $this->importService->processImport($parsed['data'], $mapping);

        return new JsonResponse($stats);
    }

    #[Route('/articles', name: 'articles', methods: ['POST'])]
    #[OA\Post(
        summary: "Import only articles.",
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
                        new OA\Property(property: "delimiter", type: "string", example: ";")
                    ]
                )
            )
        ]
    )]
    public function importArticles(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        $delimiter = $request->request->get('delimiter', ';');
        $mappingJson = $request->request->get('mapping');
        $mapping = json_decode($mappingJson, true);

        if (!$file || !$mapping) {
            return new JsonResponse(['error' => 'Missing file or mapping'], 400);
        }

        $content = file_get_contents($file->getPathname());
        $parsed = $this->importService->parseCsvContent($content, $delimiter);

        $stats = $this->importService->importArticles($parsed['data'], $mapping);

        return new JsonResponse($stats);
    }

    #[Route('/cars', name: 'cars', methods: ['POST'])]
    #[OA\Post(
        summary: "Import cars (and optionally link to articles).",
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
                        new OA\Property(property: "article_identifier_field", type: "string", description: "Field in mapping that connects to Article (default: code)", example: "code")
                    ]
                )
            )
        ]
    )]
    public function importCars(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        $delimiter = $request->request->get('delimiter', ';');
        $mappingJson = $request->request->get('mapping');
        $mapping = json_decode($mappingJson, true);
        $articleIdentifierField = $request->request->get('article_identifier_field', 'code'); // Default to 'code' as requested

        if (!$file || !$mapping) {
            return new JsonResponse(['error' => 'Missing file or mapping'], 400);
        }

        $content = file_get_contents($file->getPathname());
        $parsed = $this->importService->parseCsvContent($content, $delimiter);

        $stats = $this->importService->importCars($parsed['data'], $mapping, $articleIdentifierField);

        return new JsonResponse($stats);
    }
}
