<?php

namespace App\Controller;

use App\Entity\ImportMapping;
use App\Service\ImportMappingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

#[Route('/api/import/mappings', name: 'api_import_mappings_')]
#[OA\Tag(name: 'Import Mappings')]
class ImportMappingController extends AbstractController
{
    public function __construct(
        private ImportMappingService $importMappingService
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    #[OA\Get(
        summary: "Lista wszystkich zmapowanych pól importowanego pliku.",
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista zmapowanych pól importowanego pliku.",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id", type: "integer"),
                            new OA\Property(property: "name", type: "string"),
                            new OA\Property(property: "mapping", type: "object"),
                            new OA\Property(property: "createdAt", type: "string", format: "date-time")
                        ]
                    )
                )
            )
        ]
    )]
    public function index(): JsonResponse
    {
        $mappings = $this->importMappingService->getAllMappings();

        $data = array_map(fn(ImportMapping $mapping) => [
            'id' => $mapping->getId(),
            'name' => $mapping->getName(),
            'mapping' => $mapping->getMapping(),
            'createdAt' => $mapping->getCreatedAt()->format('c'),
        ], $mappings);

        return $this->json($data);
    }

    #[Route('/{name}', name: 'show', methods: ['GET'])]
    #[OA\Get(
        summary: "Pobierz konkretne mapowanie pól importowanego pliku.",
        responses: [
            new OA\Response(
                response: 200,
                description: "Szczegóły mapowania pól importowanego pliku.",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer"),
                        new OA\Property(property: "name", type: "string"),
                        new OA\Property(property: "mapping", type: "object"),
                        new OA\Property(property: "createdAt", type: "string", format: "date-time")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Mapowanie pól importowanego pliku nie znalezione.")
        ]
    )]
    public function show(string $name): JsonResponse
    {
        $mapping = $this->importMappingService->getMappingByName($name);

        if (!$mapping) {
            return $this->json(['error' => 'Mapowanie pól importowanego pliku nie znalezione.'], 404);
        }

        return $this->json([
            'id' => $mapping->getId(),
            'name' => $mapping->getName(),
            'mapping' => $mapping->getMapping(),
            'createdAt' => $mapping->getCreatedAt()->format('c'),
        ]);
    }

    #[Route('', name: 'store', methods: ['POST'])]
    #[OA\Post(
        summary: "Zapisz mapowanie pól importowanego pliku.",
        requestBody: new OA\RequestBody(
            description: "Dane mapowania pól importowanego pliku.",
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string", example: "default_articles"),
                    new OA\Property(property: "mapping", type: "object", example: ["code" => "sku", "name" => "title"])
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Zapisane mapowanie pól importowanego pliku.",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer"),
                        new OA\Property(property: "name", type: "string"),
                        new OA\Property(property: "mapping", type: "object"),
                        new OA\Property(property: "createdAt", type: "string", format: "date-time")
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Niepoprawne dane wejściowe.")
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['name']) || empty($data['name'])) {
            return $this->json(['error' => 'Nazwa jest wymagana.'], 400);
        }

        if (!isset($data['mapping']) || !is_array($data['mapping'])) {
            return $this->json(['error' => 'Obiekt mapowania jest wymagany.'], 400);
        }

        $mapping = $this->importMappingService->saveMapping($data['name'], $data['mapping']);

        return $this->json([
            'id' => $mapping->getId(),
            'name' => $mapping->getName(),
            'mapping' => $mapping->getMapping(),
            'createdAt' => $mapping->getCreatedAt()->format('c'),
        ]);
    }

    #[Route('/{name}', name: 'delete', methods: ['DELETE'])]
    #[OA\Delete(
        summary: "Usuń mapowanie pól importowanego pliku.",
        responses: [
            new OA\Response(response: 204, description: "Mapowanie pól importowanego pliku usunięte."),
            new OA\Response(response: 404, description: "Mapowanie pól importowanego pliku nie znalezione.")
        ]
    )]
    public function delete(string $name): JsonResponse
    {
        $mapping = $this->importMappingService->getMappingByName($name);
        
        if (!$mapping) {
            return $this->json(['error' => 'Mapowanie pól importowanego pliku nie znalezione.'], 404);
        }

        $this->importMappingService->deleteMapping($name);

        return $this->json(null, 204);
    }
}
