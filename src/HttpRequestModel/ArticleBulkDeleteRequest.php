<?php

namespace App\HttpRequestModel;

use OpenApi\Attributes as OA;

class ArticleBulkDeleteRequest
{
    #[OA\Property(description: "Typ usuwania: 'list' (po id) lub 'filtered' (po filtrach)", example: "list")]
    public string $type = 'list';

    #[OA\Property(description: "Lista ID artykuĹĂłw (wymagane dla type='list')", type: "array", items: new OA\Items(type: "integer"))]
    public array $list = [];

    #[OA\Property(description: "Filtry (wymagane dla type='filtered')", type: "object")]
    public array $filters = [];
}