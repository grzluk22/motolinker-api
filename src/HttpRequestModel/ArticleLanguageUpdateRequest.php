<?php

namespace App\HttpRequestModel;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ArticleLanguageUpdateRequest",
    description: "Request body do aktualizacji tłumaczenia artykułu",
    type: "object",
    properties: [
        new OA\Property(property: "name", type: "string", example: "Nowa nazwa produktu"),
        new OA\Property(property: "description", type: "string", example: "Nowy opis produktu")
    ]
)]
class ArticleLanguageUpdateRequest
{
    public ?string $name = null;
    public ?string $description = null;
}
