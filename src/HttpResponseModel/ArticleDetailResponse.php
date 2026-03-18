<?php

namespace App\HttpResponseModel;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ArticleDetailResponse",
    description: "Szczegóły artykułu z tłumaczeniami i listą EAN",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "code", type: "string", example: "36790-SET-MS"),
        new OA\Property(property: "ean13", type: "string", example: "1234567890123"),
        new OA\Property(property: "ean13_list", type: "array", items: new OA\Items(type: "string"), example: ["1234567890123", "5901234123457"]),
        new OA\Property(property: "price", type: "number", format: "float", example: 367.99),
        new OA\Property(property: "id_category", type: "integer", example: 0),
        new OA\Property(property: "name", type: "string", example: "Zestaw zawieszenia"),
        new OA\Property(property: "description", type: "string", example: "Zawieszenie do Audi A3"),
        new OA\Property(
            property: "translations",
            type: "array",
            items: new OA\Items(
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1),
                    new OA\Property(property: "id_article", type: "integer", example: 1),
                    new OA\Property(property: "id_language", type: "integer", example: 1),
                    new OA\Property(property: "name", type: "string", example: "PL nazwa"),
                    new OA\Property(property: "description", type: "string", example: "PL opis")
                ]
            )
        )
    ]
)]
class ArticleDetailResponse
{
    public int $id;
    public string $code;
    public string $ean13;
    public array $ean13_list;
    public float $price;
    public int $id_category;
    public string $name;
    public string $description;
    public array $translations;
}
