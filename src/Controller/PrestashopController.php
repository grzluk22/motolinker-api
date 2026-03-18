<?php

namespace App\Controller;

use App\Entity\ExternalDatabase;
use App\HttpResponseModel\PrestashopCategory;
use App\HttpResponseModel\PrestashopProduct;
use App\HttpResponseModel\PrestashopProductDetails;
use App\Repository\ExternalDatabaseRepository;
use App\Service\PrestashopClientService;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Messenger\MessageBusInterface;
use App\Entity\ImportJob;
use App\Message\PrestashopImportMessage;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/api/prestashop')]
class PrestashopController extends AbstractController
{
    public function __construct(
        private PrestashopClientService $prestashopClientService,
        private ExternalDatabaseRepository $databaseRepository,
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $bus
    ) {
    }

    #[Route('/{databaseId}/products', name: 'api_prestashop_products', methods: ['GET'])]
    #[OA\Get(
        summary: 'Pobierz listę produktów z Prestashop',
        description: 'Zwraca znormalizowaną listę produktów z wybranego sklepu Prestashop. Umożliwia filtrowanie, paginację (limit, offset).'
    )]
    #[OA\Parameter(
        name: 'databaseId',
        in: 'path',
        description: 'ID wpisu ExternalDatabase',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'limit',
        in: 'query',
        description: 'Maksymalna liczba zwracanych elementów',
        required: false,
        schema: new OA\Schema(type: 'integer', default: 10)
    )]
    #[OA\Parameter(
        name: 'offset',
        in: 'query',
        description: 'Liczba pomijanych elementów (offset)',
        required: false,
        schema: new OA\Schema(type: 'integer', default: 0)
    )]
    #[OA\Parameter(
        name: 'filter[name]',
        in: 'query',
        description: 'Filtrowanie po nazwie produktu. Przykładowo: filter[name]=%[szukana fraza]%',
        required: false,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'Lista produktów',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: PrestashopProduct::class))
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Baza danych nie została znaleziona'
    )]
    #[OA\Tag(name: 'Prestashop')]
    public function getProducts(int $databaseId, Request $request): JsonResponse
    {
        $database = $this->databaseRepository->find($databaseId);
        
        if (!$database) {
            return $this->json(['error' => 'Database not found'], 404);
        }

        $limit = $request->query->getInt('limit', 10);
        $offset = $request->query->getInt('offset', 0);
        
        // Wyciąganie parametrów filter[...] z querystringa
        $filters = [];
        $queryFilters = $request->query->all('filter') ?? [];
        if (is_array($queryFilters)) {
            $filters = $queryFilters;
        }

        try {
            $products = $this->prestashopClientService->getProducts($database, $filters, $limit, $offset);
            return $this->json($products);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/{databaseId}/products/{productReference}', name: 'api_prestashop_product_details', methods: ['GET'])]
    #[OA\Get(
        summary: 'Pobierz szczegóły produktu z Prestashop po referencji',
        description: 'Zwraca wszystkie dostępne szczegóły produktu (w tym URL-e zdjęć produktu z uwzględnieniem hosta i klucza dostępu tak by można było je pobrać).'
    )]
    #[OA\Parameter(
        name: 'databaseId',
        in: 'path',
        description: 'ID wpisu ExternalDatabase',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'productReference',
        in: 'path',
        description: 'Nr referencyjny (kod) produktu',
        required: true,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'Szczegóły produktu',
        content: new OA\JsonContent(ref: new Model(type: PrestashopProductDetails::class))
    )]
    #[OA\Response(
        response: 404,
        description: 'Baza danych lub produkt nie został znaleziony'
    )]
    #[OA\Tag(name: 'Prestashop')]
    public function getProductDetails(int $databaseId, string $productReference): JsonResponse
    {
        $database = $this->databaseRepository->find($databaseId);
        
        if (!$database) {
            return $this->json(['error' => 'Database not found'], 404);
        }

        try {
            $productDetails = $this->prestashopClientService->getProductDetailsByReference($database, $productReference);
            
            if (!$productDetails) {
                return $this->json(['error' => 'Product not found'], 404);
            }

            return $this->json($productDetails);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/{databaseId}/categories', name: 'api_prestashop_categories', methods: ['GET'])]
    #[OA\Get(
        summary: 'Pobierz drzewo kategorii z Prestashop',
        description: 'Zwraca znormalizowane drzewo kategorii z wybranego sklepu Prestashop, uporządkowane hierarchicznie.'
    )]
    #[OA\Parameter(
        name: 'databaseId',
        in: 'path',
        description: 'ID wpisu ExternalDatabase',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Drzewo kategorii',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: PrestashopCategory::class))
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Baza danych nie została znaleziona'
    )]
    #[OA\Tag(name: 'Prestashop')]
    public function getCategories(int $databaseId): JsonResponse
    {
        $database = $this->databaseRepository->find($databaseId);
        
        if (!$database) {
            return $this->json(['error' => 'Database not found'], 404);
        }

        try {
            $categoriesTree = $this->prestashopClientService->getCategories($database);
            return $this->json($categoriesTree);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/{databaseId}/import/start', name: 'api_prestashop_import_start', methods: ['POST'])]
    #[OA\Post(
        summary: 'Uruchamia asynchroniczny w tle zadanie importowania produktów z PrestaShop',
        description: 'Tworzy w bazie ImportTask i zwraca jego ID. Następnie Messenger popycha je dalej.',
    )]
    #[OA\Parameter(
        name: 'databaseId',
        in: 'path',
        description: 'ID wpisu ExternalDatabase',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'productIds', type: 'array', items: new OA\Items(type: 'integer'), example: [10, 15, 20]),
                new OA\Property(property: 'type', type: 'string', example: 'articles', description: 'Typ importu: articles lub categories'),
                new OA\Property(property: 'filters', type: 'object', example: ['active' => '1', 'reference' => 'br', 'price_min' => 100, 'price_max' => 200, 'quantity_min' => 2, 'quantity_max' => 10])
            ],
            type: 'object'
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Uruchomiono import',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'task_id', type: 'integer', example: 123)
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Brak lub błędne parametry wejściowe')]
    #[OA\Response(response: 404, description: 'Nie znaleziono bazy danych w systemie')]
    #[OA\Tag(name: 'Prestashop')]
    public function startImport(int $databaseId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $productIds = $data['productIds'] ?? [];
        $rawFilters = $data['filters'] ?? [];
        $type = $data['type'] ?? 'articles';
        $filters = [];

        // Rekonstrukcja płaskich zagnieżdżeń np. price[gte] na ['price']['gte']  
        // dla ujednolicenia JSON body ze standardowym $_GET w PHP
        if (is_array($rawFilters)) {
            foreach ($rawFilters as $key => $value) {
                if (preg_match('/^([^\[]+)\[([^\]]+)\]?$/', $key, $matches)) {
                    $mainKey = $matches[1];
                    $subKey = $matches[2];
                    
                    if (!isset($filters[$mainKey]) || !is_array($filters[$mainKey])) {
                        $filters[$mainKey] = [];
                    }
                    $filters[$mainKey][$subKey] = $value;
                } else {
                    $filters[$key] = $value;
                }
            }
        }

        if (!$databaseId) {
            return $this->json(['error' => 'Brak wymaganego pola databaseId'], 400);
        }

        $database = $this->databaseRepository->find($databaseId);
        if (!$database) {
            return $this->json(['error' => 'Podana baza danych nie istnieje'], 404);
        }

        $job = new ImportJob();
        $job->setSource('prestashop');
        $job->setExternalDatabase($database);
        $job->setImportType($type);
        
        if (is_array($filters) && !empty($filters)) {
            $job->setSourceFilters($filters);
        }

        // Puste array oznacza importuj wszystko
        if (is_array($productIds) && !empty($productIds)) {
             $job->setSourceIds($productIds);
        } else {
             $job->setSourceIds([]);
        }

        $this->entityManager->persist($job);
        $this->entityManager->flush();

        // Uruchamiamy kolejkę podając ID wygenerowanego Job'a
        $this->bus->dispatch(new PrestashopImportMessage($job->getId()));

        return $this->json(['task_id' => $job->getId()]);
    }

    #[Route('/{databaseId}/search', name: 'api_prestashop_search', methods: ['POST'])]
    #[OA\Post(
        summary: 'Wyszukaj produkty z Prestashop',
        description: 'Pełnotekstowe wyszukiwanie produktów LIKE po wybranych polach. Obsługuje paginację, sortowanie oraz dodatkowe filtry.'
    )]
    #[OA\Parameter(
        name: 'databaseId',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'text', type: 'string', example: 'rower', description: 'Szukana fraza – automatycznie owrapowana w %%'),
                new OA\Property(
                    property: 'fields',
                    type: 'array',
                    items: new OA\Items(type: 'string', enum: ['name', 'reference', 'description', 'category_name']),
                    example: ['name', 'category_name'],
                    description: 'Pola, w których ma być szukana fraza'
                ),
                new OA\Property(property: 'limit', type: 'integer', example: 10, description: 'Liczba wyników na stronę'),
                new OA\Property(property: 'offset', type: 'integer', example: 0, description: 'Offset paginacji'),
                new OA\Property(property: 'sort_by', type: 'string', example: 'name', description: 'Pole sortowania (id, name, price, reference, date_add, date_upd)'),
                new OA\Property(property: 'sort_dir', type: 'string', example: 'asc', description: 'Kierunek sortowania: asc lub desc'),
                new OA\Property(property: 'filters', type: 'object', example: ['active' => '1'], description: 'Dodatkowe filtry produktów (np. active)')
            ],
            type: 'object',
            required: ['text', 'fields']
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Lista produktów pasujących do zapytania',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: PrestashopProduct::class))
        )
    )]
    #[OA\Response(response: 400, description: 'Brak lub błędne pola: text, fields')]
    #[OA\Response(response: 404, description: 'Baza danych nie znaleziona')]
    #[OA\Tag(name: 'Prestashop')]
    public function search(int $databaseId, Request $request): JsonResponse
    {
        $database = $this->databaseRepository->find($databaseId);
        if (!$database) {
            return $this->json(['error' => 'Database not found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        $text = trim($data['text'] ?? '');
        $fields = $data['fields'] ?? [];

        if ($text === '') {
            return $this->json(['error' => 'Pole text jest wymagane'], 400);
        }

        if (empty($fields) || !is_array($fields)) {
            return $this->json(['error' => 'Pole fields jest wymagane i musi być tablicą'], 400);
        }

        $allowedFields = ['name', 'reference', 'description', 'category_name'];
        $fields = array_values(array_intersect($fields, $allowedFields));

        if (empty($fields)) {
            return $this->json(['error' => 'Brak poprawnych pól. Dozwolone: ' . implode(', ', $allowedFields)], 400);
        }

        $limit      = max(1, (int) ($data['limit'] ?? 10));
        $offset     = max(0, (int) ($data['offset'] ?? 0));
        $sortBy     = $data['sort_by'] ?? 'id';
        $sortDir    = strtolower($data['sort_dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $rawFilters = $data['filters'] ?? [];

        try {
            $products = $this->prestashopClientService->searchProducts(
                $database,
                $text,
                $fields,
                $limit,
                $offset,
                is_array($rawFilters) ? $rawFilters : [],
                $sortBy,
                $sortDir
            );

            return $this->json($products);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/settings', name: 'api_prestashop_settings_list', methods: ['GET'])]
    #[OA\Get(
        summary: 'Pobierz pobrane konfiguracje połączeń Prestashop',
        description: 'Zwraca wszystkie dodane konfiguracje serwerów Prestashop. Ze względów bezpieczeństwa klucz API nie jest wysyłany w tej odpowiedzi.'
    )]
    #[OA\Response(
        response: 200,
        description: 'Lista połączonych baz danych PrestaShop',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 1),
                    new OA\Property(property: 'name', type: 'string', example: 'Mój sklep Presta'),
                    new OA\Property(property: 'apiUrl', type: 'string', example: 'https://mojsklep.pl'),
                    new OA\Property(property: 'type', type: 'string', example: 'prestashop')
                ],
                type: 'object'
            )
        )
    )]
    #[OA\Tag(name: 'Prestashop Settings')]
    public function getSettings(): JsonResponse
    {
        $databases = $this->databaseRepository->findBy(['type' => 'prestashop']);
        
        $result = array_map(function (ExternalDatabase $db) {
            return [
                'id' => $db->getId(),
                'name' => $db->getName(),
                'apiUrl' => $db->getApiUrl(),
                'type' => $db->getType()
            ];
        }, $databases);

        return $this->json($result);
    }

    #[Route('/settings', name: 'api_prestashop_settings_create', methods: ['POST'])]
    #[OA\Post(
        summary: 'Dodaj nowe połączenie Prestashop',
        description: 'Tworzy nową encję bazy dla serwera PrestaShop.'
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Mój sklep Presta'),
                new OA\Property(property: 'apiUrl', type: 'string', example: 'https://mojsklep.pl'),
                new OA\Property(property: 'apiKey', type: 'string', example: 'ABCX1234')
            ],
            type: 'object',
            required: ['name', 'apiUrl', 'apiKey']
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Pomyślnie dodano'
    )]
    #[OA\Response(response: 400, description: 'Brakujące dane lub błędny URL')]
    #[OA\Tag(name: 'Prestashop Settings')]
    public function createSetting(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['name']) || empty($data['apiUrl']) || empty($data['apiKey'])) {
            return $this->json(['error' => 'Brakujące pola: name, apiUrl, apiKey'], 400);
        }

        if (!filter_var($data['apiUrl'], FILTER_VALIDATE_URL)) {
            return $this->json(['error' => 'Błędny format URL'], 400);
        }

        $db = new ExternalDatabase();
        $db->setName($data['name']);
        $db->setApiUrl($data['apiUrl']);
        $db->setApiKey($data['apiKey']);
        $db->setType('prestashop');

        $this->entityManager->persist($db);
        $this->entityManager->flush();

        return $this->json(['id' => $db->getId(), 'message' => 'Zapisano pomyślnie'], 201);
    }

    #[Route('/settings/{id}', name: 'api_prestashop_settings_update', methods: ['PUT'])]
    #[OA\Put(
        summary: 'Edytuj połączenie Prestashop',
        description: 'Aktualizuje parametry serwera.'
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID wpisu',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Mój sklep Presta'),
                new OA\Property(property: 'apiUrl', type: 'string', example: 'https://mojsklep.pl'),
                new OA\Property(property: 'apiKey', type: 'string', example: '********')
            ],
            type: 'object'
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Konfiguracja zaktualizowana'
    )]
    #[OA\Response(response: 404, description: 'Nie znaleziono bazy')]
    #[OA\Tag(name: 'Prestashop Settings')]
    public function updateSetting(int $id, Request $request): JsonResponse
    {
        $db = $this->databaseRepository->find($id);
        if (!$db) {
            return $this->json(['error' => 'Not found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (!empty($data['name'])) {
            $db->setName($data['name']);
        }
        
        if (!empty($data['apiUrl'])) {
            if (!filter_var($data['apiUrl'], FILTER_VALIDATE_URL)) {
                return $this->json(['error' => 'Błędny format URL'], 400);
            }
            $db->setApiUrl($data['apiUrl']);
        }

        // Aktualizuj klucz tylko jeśli nie jest gwiazdkami albo pusty
        if (!empty($data['apiKey']) && !preg_match('/^\*+$/', $data['apiKey'])) {
            $db->setApiKey($data['apiKey']);
        }

        $this->entityManager->flush();

        return $this->json(['message' => 'Zaktualizowano']);
    }

    #[Route('/settings/{id}', name: 'api_prestashop_settings_delete', methods: ['DELETE'])]
    #[OA\Delete(
        summary: 'Usuń konfigurację Prestashop',
        description: 'Usuwa bazę o podanym ID.'
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID wpisu do skasowania',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 204,
        description: 'Usunięto pomyślnie'
    )]
    #[OA\Response(response: 404, description: 'Nie znaleziono bazy')]
    #[OA\Tag(name: 'Prestashop Settings')]
    public function deleteSetting(int $id): JsonResponse
    {
        $db = $this->databaseRepository->find($id);
        if (!$db) {
            return $this->json(['error' => 'Not found'], 404);
        }

        $this->entityManager->remove($db);
        $this->entityManager->flush();

        return $this->json(null, 204);
    }
}
