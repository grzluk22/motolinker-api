<?php

namespace App\HttpResponseModel;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ArticleCriterionResponse",
    description: "Odpowiedź zawierająca kryterium artykułu",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "id_article", type: "integer", example: 26),
        new OA\Property(property: "id_crterion", type: "integer", example: 2),
        new OA\Property(property: "value", type: "string", example: "F"),
        new OA\Property(property: "value_description", type: "string", example: "Front"),
        new OA\Property(
            property: "translations",
            type: "array",
            items: new OA\Items(
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1),
                    new OA\Property(property: "id_article_criterion", type: "integer", example: 1),
                    new OA\Property(property: "id_language", type: "integer", example: 1),
                    new OA\Property(property: "value_description", type: "string", example: "Przód")
                ]
            )
        )
    ]
)]
class ArticleCriterionResponse
{
    public int $id;
    public int $id_article;
    public int $id_crterion;
    public string $value;
    public string $value_description;
    public array $translations;
}
