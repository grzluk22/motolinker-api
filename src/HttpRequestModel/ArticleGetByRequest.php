<?php

namespace App\HttpRequestModel;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ArticleGetByRequest",
    description: "Request body do wyszukiwania artykułów z filtrami",
    type: "object",
    properties: [
        new OA\Property(
            property: "criteria",
            type: "object",
            properties: [
                new OA\Property(property: "code", type: "string", example: "36790-SET-MS"),
                new OA\Property(property: "ean13", type: "string", example: "1234567890123"),
                new OA\Property(property: "price", type: "number", format: "float", example: 367.99),
                new OA\Property(property: "id_category", type: "integer", example: 0),
                new OA\Property(property: "name", type: "string", example: "Zestaw zawieszenia"),
                new OA\Property(property: "description", type: "string", example: "Zawieszenie do Audi A5 b6"),
                new OA\Property(property: "searchLike", type: "boolean", example: true),
                new OA\Property(property: "image", type: "boolean", example: true)
            ]
        ),
        new OA\Property(property: "orderBy", type: "object", example: ["id" => "DESC"]),
        new OA\Property(property: "limit", type: "integer", example: 20),
        new OA\Property(property: "offset", type: "integer", example: 40)
    ]
)]
class ArticleGetByRequest
{
    public ?array $criteria = null;
    public ?array $orderBy = null;
    public ?int $limit = null;
    public ?int $offset = null;
}
