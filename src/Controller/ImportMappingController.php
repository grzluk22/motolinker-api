<?php

namespace App\Controller;

use App\Entity\ImportMapping;
use App\Service\ImportMappingService;
use App\Service\SchemaProviderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

#[Route('/api/import-mappings', name: 'api_import_mappings_')]
#[OA\Tag(name: 'Import Mappings')]
class ImportMappingController extends AbstractController
{
    public function __construct(
        private ImportMappingService $importMappingService,
        private SchemaProviderService $schemaProviderService
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    #[OA\Get(
        summary: "Lista zdefiniowanych szablonów mapowania.",
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista szablonów.",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id", type: "integer"),
                            new OA\Property(property: "name", type: "string"),
                            new OA\Property(property: "is_default", type: "boolean"),
                            new OA\Property(property: "mapping_data", type: "object"),
                            new OA\Property(property: "uniqueness_field", type: "string"),
                            new OA\Property(property: "on_duplicate_action", type: "string"),
                            new OA\Property(property: "fields_to_update", type: "array", items: new OA\Items(type: "string"), nullable: true)
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
            'is_default' => $mapping->isDefault(),
            'mapping_data' => $mapping->getMappingData(),
            'uniqueness_field' => $mapping->getUniquenessField(),
            'on_duplicate_action' => $mapping->getOnDuplicateAction(),
            'fields_to_update' => $mapping->getFieldsToUpdate(),
        ], $mappings);

        return $this->json($data);
    }

    #[Route('/schema', name: 'schema', methods: ['GET'])]
    #[OA\Get(
        summary: "Zwraca dostępne pola systemu lokalnego do zmapowania.",
        responses: [
            new OA\Response(
                response: 200,
                description: "Zbiór dostępnych pól (słownik).",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id", type: "string", description: "Identyfikator pola"),
                            new OA\Property(property: "label", type: "string", description: "Nazwa wyświetlana")
                        ]
                    )
                )
            )
        ]
    )]
    public function schema(): JsonResponse
    {
        return $this->json($this->schemaProviderService->getAvailableLocalFields());
    }

    #[Route('', name: 'store', methods: ['POST'])]
    #[OA\Post(
        summary: "Stworzenie nowego szablonu mapowania.",
        requestBody: new OA\RequestBody(
            description: "Struktura wymaga podania nazwy i szczegółów mapowania.",
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Mapowanie Standardowe"),
                    new OA\Property(property: "is_default", type: "boolean", example: true),
                    new OA\Property(
                        property: "mapping_data",
                        type: "object",
                        example: ["sku_zewnetrzne" => "sku", "nazwa_zewnetrzna" => "name"],
                        description: "Mapa kluczZewnetrzny => kluczLokalny"
                    ),
                    new OA\Property(property: "uniqueness_field", type: "string", example: "sku", description: "Pole używane do sprawdzenia czy artykuł już istnieje, domyślnie 'article_code'"),
                    new OA\Property(property: "on_duplicate_action", type: "string", example: "update_selected", description: "Akcja przy napotkaniu blinzniaczego (istniejącego) artykułu (update_all, update_selected, skip)"),
                    new OA\Property(property: "fields_to_update", type: "array", items: new OA\Items(type: "string"), example: ["price_net", "quantity"], description: "Tablica powiązań np. co uaktualniać przy 'update_selected'")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Pomyślnie utworzono szablon."),
            new OA\Response(response: 400, description: "Niepoprawne zapytanie.")
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['name'])) {
            return $this->json(['error' => 'Brak obiektu lub wymaganej nazwy szablonu.'], 400);
        }

        try {
            $mapping = $this->importMappingService->createMapping($data);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json([
            'id' => $mapping->getId(),
            'name' => $mapping->getName()
        ]);
    }

    #[Route('/{id}', name: 'update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    #[OA\Put(
        summary: "Aktualizacja szablonu mapowania.",
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Mapowanie Zaktualizowane"),
                    new OA\Property(property: "is_default", type: "boolean"),
                    new OA\Property(property: "mapping_data", type: "object"),
                    new OA\Property(property: "uniqueness_field", type: "string"),
                    new OA\Property(property: "on_duplicate_action", type: "string"),
                    new OA\Property(property: "fields_to_update", type: "array", items: new OA\Items(type: "string"))
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Zaktualizowano pomyślnie."),
            new OA\Response(response: 404, description: "Szablon nie odnaleziony."),
            new OA\Response(response: 400, description: "Błędne dane.")
        ]
    )]
    public function update(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $mapping = $this->importMappingService->updateMapping($id, $data ?: []);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json([
            'id' => $mapping->getId(),
            'name' => $mapping->getName()
        ]);
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    #[OA\Delete(
        summary: "Usunięcie szablonu mapowania.",
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ]
    )]
    public function delete(int $id): JsonResponse
    {
        try {
            $this->importMappingService->deleteMapping($id);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json(null, 204);
    }

    #[Route('/bulk', name: 'bulk_delete', methods: ['DELETE'])]
    #[OA\Delete(
        summary: "Masowe usunięcie szablonów mapowania.",
        requestBody: new OA\RequestBody(
            description: "Lista ID szablonów do usunięcia",
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "ids", type: "array", items: new OA\Items(type: "integer"), example: [1, 2, 3])
                ]
            )
        ),
        responses: [
            new OA\Response(response: 204, description: "Pomyślnie usunięto szablony."),
            new OA\Response(response: 400, description: "Błędne żądanie lub niepoprawny format danych.")
        ]
    )]
    public function bulkDelete(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $ids = $data['ids'] ?? [];

        if (!is_array($ids)) {
            return $this->json(['error' => 'Nieprawidłowy format danych, oczekiwano tablicy ids.'], 400);
        }

        try {
            $this->importMappingService->bulkDeleteMapping($ids);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json(null, 204);
    }
}
