<?php

namespace App\HttpRequestModel;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ArticleLanguageCreateRequest",
    description: "Request body do tworzenia tłumaczenia artykułu",
    type: "object",
    required: ["id_language", "name", "description"],
    properties: [
        new OA\Property(property: "id_language", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "Komplet hamulcowy"),
        new OA\Property(property: "description", type: "string", example: "Opis produktu w wybranym języku")
    ]
)]
class ArticleLanguageCreateRequest
{
    public int $id_language;
    public string $name;
    public string $description;
}
