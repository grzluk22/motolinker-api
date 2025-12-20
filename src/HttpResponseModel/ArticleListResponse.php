<?php

namespace App\HttpResponseModel;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ArticleListResponse",
    description: "Odpowiedź zawierająca listę artykułów",
    type: "object",
    properties: [
        new OA\Property(property: "data", type: "array", items: new OA\Items(
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(property: "code", type: "string", example: "36790-SET-MS"),
                new OA\Property(property: "ean13", type: "string", example: "1234567890123"),
                new OA\Property(property: "ean13_list", type: "array", items: new OA\Items(type: "string"), example: ["1234567890123", "5901234123457"]),
                new OA\Property(property: "price", type: "number", format: "float", example: 367.99),
                new OA\Property(property: "name", type: "string", example: "Zestaw zawieszenia"),
                new OA\Property(property: "description", type: "string", example: "Zawieszenie do Audi A3"),
                new OA\Property(property: "id_category", type: "integer", example: 0),
                new OA\Property(property: "thumbnail_url", type: "string", nullable: true, example: "/uploads/articles/1/thumbnails/abc123.jpg")
            ]
        )),
        new OA\Property(property: "total", type: "integer", example: 1)
    ]
)]
class ArticleListResponse
{
    public array $data;
    public int $total;
}
