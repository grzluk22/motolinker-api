<?php

namespace App\HttpRequestModel;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ArticleCriterionCreateRequest",
    description: "Request body do dodawania kryterium do artykułu",
    type: "object",
    required: ["id_article", "id_criterion", "value", "value_description"],
    properties: [
        new OA\Property(property: "id_article", type: "integer", example: 6),
        new OA\Property(property: "id_criterion", type: "integer", example: 1),
        new OA\Property(property: "value", type: "string", example: "P"),
        new OA\Property(property: "value_description", type: "string", example: "Przód"),
        new OA\Property(
            property: "translations",
            type: "array",
            items: new OA\Items(
                properties: [
                    new OA\Property(property: "id_language", type: "integer", example: 1),
                    new OA\Property(property: "value_description", type: "string", example: "Przód")
                ]
            )
        )
    ]
)]
class ArticleCriterionCreateRequest
{
    public int $id_article;
    public int $id_criterion;
    public string $value;
    public string $value_description;
    public ?array $translations = null;
}
