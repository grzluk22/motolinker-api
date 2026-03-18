<?php

namespace App\Service;

use App\Entity\ExternalDatabase;
use App\HttpResponseModel\PrestashopCategory;
use App\HttpResponseModel\PrestashopProduct;
use App\HttpResponseModel\PrestashopProductDetails;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use InvalidArgumentException;

class PrestashopClientService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient
    ) {
    }

    public function getProducts(ExternalDatabase $database, array $filters = [], int $limit = 10, int $offset = 0): array
    {
        $this->validateDatabase($database);

        $hasQuantityFilter = isset($filters['quantity_min']) 
            || isset($filters['quantity_max']) 
            || isset($filters['quantity'])
            || isset($filters['quantity[gte]'])
            || isset($filters['quantity[gte'])
            || isset($filters['quantity[lte]'])
            || isset($filters['quantity[lte']);

        if ($hasQuantityFilter) {
            // Ponieważ Prestashop nie pozwala filtrować pola 'quantity' przez /api/products,
            // używamy getProductIds, które potajemnie łączy endpoint stock_availables z products.
            $allIdsMap = $this->getProductIds($database, $filters);
            $allIdsArray = array_keys($allIdsMap);
            $slicedIds = array_slice($allIdsArray, $offset, $limit);

            if (empty($slicedIds)) {
                return [];
            }

            $query = [
                'ws_key' => $database->getApiKey(),
                'output_format' => 'JSON',
                'display' => 'full',
                'filter[id]' => '[' . implode('|', $slicedIds) . ']'
            ];
            
            $response = $this->httpClient->request('GET', rtrim($database->getApiUrl(), '/') . '/api/products', [
                'query' => $query
            ]);

            $data = $response->toArray(false);
            if (isset($data['products']) && is_array($data['products'])) {
                return $data['products'];
            }
            return [];
        }

        // Standardowy przepływ bazujący stricte na /api/products z paginacją
        $query = [
            'ws_key' => $database->getApiKey(),
            'output_format' => 'JSON',
            'display' => 'full',
            'limit' => sprintf('%d,%d', $offset, $limit)
        ];

        $query = array_merge($query, $this->buildPrestashopFilters($filters));

        $response = $this->httpClient->request('GET', rtrim($database->getApiUrl(), '/') . '/api/products', [
            'query' => $query
        ]);

        $data = $response->toArray(false);

        if (isset($data['products']) && is_array($data['products'])) {
            return $data['products'];
        }

        return [];
    }

    /**
     * @return array<int, string|null>
     */
    public function getProductIds(ExternalDatabase $database, array $filters = []): array
    {
        $this->validateDatabase($database);

        // Wyciągamy na bok filtry związane z ilością (idą do innego endpointu)
        $quantityFilters = [];
        $hasQuantityFilter = false;
        if (isset($filters['quantity_min'])) {
            $quantityFilters['quantity_min'] = $filters['quantity_min'];
            $hasQuantityFilter = true;
        }
        if (isset($filters['quantity_max'])) {
            $quantityFilters['quantity_max'] = $filters['quantity_max'];
            $hasQuantityFilter = true;
        }
        if (isset($filters['quantity'])) {
            $quantityFilters['quantity'] = $filters['quantity'];
            $hasQuantityFilter = true;
        }

        // Dodatkowo wyciągamy formy z niedomkniętymi nawiasami lub płaskie stringi
        foreach (['quantity[gte]', 'quantity[gte', 'quantity[lte]', 'quantity[lte'] as $k) {
            if (isset($filters[$k])) {
                $quantityFilters[$k] = $filters[$k];
                $hasQuantityFilter = true;
            }
        }

        $stockProductIdsMap = null;

        if ($hasQuantityFilter) {
            $stockQuery = [
                'ws_key' => $database->getApiKey(),
                'output_format' => 'JSON',
                'display' => '[id_product]',
            ];
            $stockQuery = array_merge($stockQuery, $this->buildPrestashopFilters($quantityFilters));

            $stockResponse = $this->httpClient->request('GET', rtrim($database->getApiUrl(), '/') . '/api/stock_availables', [
                'query' => $stockQuery
            ]);

            $stockData = $stockResponse->toArray(false);
            $stockProductIdsMap = [];
            if (isset($stockData['stock_availables']) && is_array($stockData['stock_availables'])) {
                foreach ($stockData['stock_availables'] as $stock) {
                    if (isset($stock['id_product'])) {
                        $stockProductIdsMap[(int)$stock['id_product']] = true;
                    }
                }
            }
        }

        $productFilters = $filters;
        unset(
            $productFilters['quantity_min'], 
            $productFilters['quantity_max'], 
            $productFilters['quantity'],
            $productFilters['quantity[gte]'],
            $productFilters['quantity[gte'],
            $productFilters['quantity[lte]'],
            $productFilters['quantity[lte']
        );

        $query = [
            'ws_key' => $database->getApiKey(),
            'output_format' => 'JSON',
            'display' => '[id,reference]'
        ];

        if (!empty($productFilters)) {
            $query = array_merge($query, $this->buildPrestashopFilters($productFilters));
        }

        $response = $this->httpClient->request('GET', rtrim($database->getApiUrl(), '/') . '/api/products', [
            'query' => $query
        ]);

        $data = $response->toArray(false);
        $ids = [];

        if (isset($data['products']) && is_array($data['products'])) {
            foreach ($data['products'] as $item) {
                if (isset($item['id'])) {
                    $prodId = (int) $item['id'];
                    // Intersection - omijamy produkty niezgodne z filtrem zasobów (quantity)
                    if ($stockProductIdsMap !== null && !isset($stockProductIdsMap[$prodId])) {
                        continue;
                    }
                    $ids[$prodId] = $item['reference'] ?? null;
                }
            }
        }

        return $ids;
    }

    public function getProductDetailsByReference(ExternalDatabase $database, string $reference): ?PrestashopProductDetails
    {
        $this->validateDatabase($database);

        $query = [
            'ws_key' => $database->getApiKey(),
            'output_format' => 'JSON',
            'display' => 'full',
            'filter[reference]' => $reference,
            'limit' => 1
        ];

        $response = $this->httpClient->request('GET', rtrim($database->getApiUrl(), '/') . '/api/products', [
            'query' => $query
        ]);

        $data = $response->toArray(false); // don't throw exception on 404
        if (empty($data['products']) || !is_array($data['products']) || count($data['products']) === 0) {
            return null;
        }

        $item = reset($data['products']);

        $price = isset($item['price']) ? (string) $item['price'] : null;
        $description = !empty($item['description']) ? $this->extractMultilangString($item['description']) : null;
        $active = isset($item['active']) ? (string) $item['active'] : null;
        $ref = $item['reference'] ?? null;
        $id = (int) $item['id'];

        $imageUrls = [];
        $baseUrl = rtrim($database->getApiUrl(), '/');
        $apiKey = $database->getApiKey();

        if (isset($item['associations']['images']) && is_array($item['associations']['images'])) {
            foreach ($item['associations']['images'] as $img) {
                if (isset($img['id'])) {
                    $imageUrls[] = sprintf('%s/api/images/products/%d/%d?ws_key=%s', $baseUrl, $id, $img['id'], $apiKey);
                }
            }
        }

        return new PrestashopProductDetails(
            id: $id,
            name: $this->extractMultilangString($item['name'] ?? ''),
            price: $price,
            description: $description,
            reference: $ref,
            active: $active,
            images: $imageUrls,
            rawDetails: $item
        );
    }

    /**
     * @return PrestashopCategory[]
     */
    public function getCategories(ExternalDatabase $database): array
    {
        $this->validateDatabase($database);

        $query = [
            'ws_key' => $database->getApiKey(),
            'output_format' => 'JSON',
            'display' => 'full'
        ];

        $response = $this->httpClient->request('GET', rtrim($database->getApiUrl(), '/') . '/api/categories', [
            'query' => $query
        ]);

        $data = $response->toArray();
        $flatCategories = [];

        if (isset($data['categories']) && is_array($data['categories'])) {
            // First pass: create DTOs
            foreach ($data['categories'] as $item) {
                // W Prestashop root kategorii zwykle ma id_parent = 0 lub brak
                $parentId = !empty($item['id_parent']) && (int)$item['id_parent'] > 0 ? (int) $item['id_parent'] : null;
                
                $flatCategories[(int) $item['id']] = new PrestashopCategory(
                    id: (int) $item['id'],
                    name: $this->extractMultilangString($item['name']),
                    parentId: $parentId
                );
            }
        }

        return $this->buildCategoryTree($flatCategories);
    }
    
    /**
     * Multi-field LIKE search across PrestaShop products.
     *
     * @param string[] $fields  Supported: 'name', 'reference', 'description', 'category_name'
     * @return array<mixed>
     */
    public function searchProducts(
        ExternalDatabase $database,
        string $text,
        array $fields,
        int $limit = 10,
        int $offset = 0,
        array $extraFilters = [],
        string $sortBy = 'id',
        string $sortDir = 'asc'
    ): array {
        $this->validateDatabase($database);

        $apiBaseUrl = rtrim($database->getApiUrl(), '/');
        $baseQuery = [
            'ws_key'        => $database->getApiKey(),
            'output_format' => 'JSON',
            'display'       => 'full',
        ];

        // --- Obsługa category_name: pobierz kategorie, filtruj lokalnie, wyciągnij ID ---
        $categoryIdFilter = null;
        if (in_array('category_name', $fields, true)) {
            $allCategories = $this->getCategories($database);
            $matchingCategoryIds = $this->findCategoryIdsByName($allCategories, $text);
            if (empty($matchingCategoryIds)) {
                // Nie ma matching kategorii → zero wyników (jeśli tylko category_name)
                if ($fields === ['category_name']) {
                    return [];
                }
            } else {
                $categoryIdFilter = '[' . implode('|', $matchingCategoryIds) . ']';
            }
        }

        // --- Buduj filtry ---
        $searchableApiFields = array_intersect($fields, ['name', 'reference', 'description']);
        $wrappedText = '%[' . $text . ']%';

        $query = $baseQuery;

        // Dodaj natywne LIKE-filtry dla pól obsługiwanych przez API
        foreach ($searchableApiFields as $field) {
            $query['filter[' . $field . ']'] = $wrappedText;
        }

        // Dodaj filter kategorii jeśli wyciągnięty
        if ($categoryIdFilter !== null) {
            $query['filter[id_category_default]'] = $categoryIdFilter;
        }

        // Dodaj dodatkowe filtry (np. active=1)
        $extraBuilt = $this->buildPrestashopFilters($extraFilters);
        $query = array_merge($query, $extraBuilt);

        // Paginacja
        $query['limit'] = sprintf('%d,%d', $offset, $limit);

        // Sortowanie
        $allowedSortFields = ['id', 'name', 'price', 'reference', 'date_add', 'date_upd'];
        if (in_array($sortBy, $allowedSortFields, true)) {
            $query['sort'] = '[' . $sortBy . '_' . strtoupper($sortDir) . ']';
        }

        $response = $this->httpClient->request('GET', $apiBaseUrl . '/api/products', [
            'query' => $query,
        ]);

        $data = $response->toArray(false);

        if (isset($data['products']) && is_array($data['products'])) {
            return $data['products'];
        }

        return [];
    }

    /**
     * Rekurencyjnie przeszukuje drzewo kategorii LIKE po nazwie i zwraca matching ID.
     *
     * @param PrestashopCategory[] $tree
     * @return int[]
     */
    private function findCategoryIdsByName(array $tree, string $text): array
    {
        $ids = [];
        $lowerText = mb_strtolower($text);

        foreach ($tree as $category) {
            if (str_contains(mb_strtolower((string) $category->name), $lowerText)) {
                $ids[] = $category->id;
            }
            if (!empty($category->children)) {
                $ids = array_merge($ids, $this->findCategoryIdsByName($category->children, $text));
            }
        }

        return $ids;
    }

    private function validateDatabase(ExternalDatabase $database): void
    {
        if ($database->getType() !== 'prestashop') {
            throw new InvalidArgumentException(sprintf('Unsupported database type: %s', $database->getType()));
        }
    }

    private function extractMultilangString(mixed $fieldData): string
    {
        if (is_string($fieldData)) {
            return $fieldData;
        }

        if (is_array($fieldData)) {
            // Check for multilang format: [ ["id" => "1", "value" => "Name"] ]
            if (isset($fieldData[0]['value'])) {
                return $fieldData[0]['value'];
            }
            
            // Other formats like associative array
            return current($fieldData) !== false ? (string) current($fieldData) : '';
        }

        return '';
    }

    private function buildPrestashopFilters(array $filters): array
    {
        $query = [];

        // Przechowywanie stanów min/max do późniejszego sklejenia
        $ranges = [
            'price' => ['min' => null, 'max' => null],
            'quantity' => ['min' => null, 'max' => null]
        ];

        foreach ($filters as $key => $val) {
            if ($val === null || $val === '') {
                continue;
            }

            // Obsługa zakresów płaskich np. price_min lub quantity_max
            if (is_scalar($val)) {
                // Skrypt PHP przy auto-encoding uderzy tutaj
                if (preg_match('/^(price|quantity)_(min|max)$/', $key, $matches)) {
                    $field = $matches[1];
                    $type = $matches[2];
                    $ranges[$field][$type] = $val;
                    continue;
                }
                // Konwencja z query pathów [gte] / [lte], uwzględniając urwany nawias przy domyślnym urlencode
                if (preg_match('/^(price|quantity)\[(gte|lte)\]?$/', $key, $matches)) {
                    $field = $matches[1];
                    $type = $matches[2] === 'gte' ? 'min' : 'max';
                    $ranges[$field][$type] = $val;
                    continue;
                }
            }

            // Obsługa zakresów zagnieżdżonych np. filter[price][gte] => $filters['price']['gte']
            if (is_array($val) && in_array($key, ['price', 'quantity'], true)) {
                if (isset($val['gte']) && $val['gte'] !== '') {
                    $ranges[$key]['min'] = (string)$val['gte'];
                }
                if (isset($val['lte']) && $val['lte'] !== '') {
                    $ranges[$key]['max'] = (string)$val['lte'];
                }
                continue;
            }

            // Pola testowe (np. name, reference) używają dopasowania typu LIKE (%...%)
            if (is_scalar($val) && in_array($key, ['name', 'reference'], true)) {
                $rawVal = trim((string)$val, '[]');
                $rawVal = trim($rawVal, '%');
                $val = '%[' . $rawVal . ']%';
            }

            // Ignorujemy natywne PHP arraye od min/max - one po odczytaniu są dozwolone jedynie jako zbudowane od-do zasięgi po pętli.
            // Spłaszczone zostały za parsowane w krokach wyżej. Głębsze tablice (poza 'price', 'quantity') odrzucamy 
            if (is_array($val)) {
                continue;
            }

            // Zwykłe filtry np. filter[name]=%, filter[active]=1
            $query[sprintf('filter[%s]', $key)] = $val;
        }

        // Sklejanie zakresów w format Prestashop [min,max]
        // Uzupełnianie brakujących progów (np. zamiast pustki dajemy duży limit), żeby uniknąć błędów API
        foreach ($ranges as $field => $bounds) {
            if ($bounds['min'] !== null || $bounds['max'] !== null) {
                $min = $bounds['min'] !== null && $bounds['min'] !== '' ? $bounds['min'] : '0';
                $max = $bounds['max'] !== null && $bounds['max'] !== '' ? $bounds['max'] : '999999999';
                
                $query[sprintf('filter[%s]', $field)] = sprintf('[%s,%s]', $min, $max);
            }
        }

        return $query;
    }

    /**
     * @param array<int, PrestashopCategory> $categoriesById
     * @return PrestashopCategory[]
     */
    private function buildCategoryTree(array $categoriesById): array
    {
        $tree = [];
        
        foreach ($categoriesById as $category) {
            if ($category->parentId === null || !isset($categoriesById[$category->parentId])) {
                // To jest węzeł główny (brak rodzica lub rodzic nie został zwrócony przez API)
                $tree[] = $category;
            } else {
                // Dodajemy jako podkategorię do rodzica
                $categoriesById[$category->parentId]->children[] = $category;
            }
        }

        return $tree;
    }
}
