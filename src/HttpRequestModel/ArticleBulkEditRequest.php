<?php

namespace App\HttpRequestModel;

use OpenApi\Attributes as OA;

class ArticleBulkEditRequest
{
    #[OA\Property(description: "Kryteria wyszukiwania artykułów do edycji", type: "object", example: ["code" => "123", "id_category" => 5])]
    public array $criteria = [];

    #[OA\Property(description: "Wartości do ustawienia w znalezionych artykułach", type: "object", example: ["price" => 100, "name" => "Nowa nazwa"])]
    public array $values = [];
}
