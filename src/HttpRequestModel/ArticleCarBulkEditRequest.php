<?php

namespace App\HttpRequestModel;

use OpenApi\Attributes as OA;

#[OA\Schema(
    description: "Payload do masowej edycji powiązań",
    example: [
        "operations" => [
            [
                "article_id" => 123,
                "car_id" => 456,
                "action" => "add"
            ],
            [
                "article_id" => 123,
                "car_id" => 789,
                "action" => "remove"
            ]
        ]
    ]
)]
class ArticleCarBulkEditRequest
{
    #[OA\Property(
        description: "Lista operacji do wykonania na powiązaniach",
        type: "array",
        items: new OA\Items(
            properties: [
                new OA\Property(
                    property: "article_id",
                    description: "ID Artykułu",
                    type: "integer",
                    example: 123
                ),
                new OA\Property(
                    property: "car_id",
                    description: "ID Samochodu",
                    type: "integer",
                    example: 456
                ),
                new OA\Property(
                    property: "action",
                    description: "Akcja do wykonania: 'add' (dodaj powiązanie) lub 'remove' (usuń powiązanie)",
                    type: "string",
                    enum: ["add", "remove"],
                    example: "add"
                )
            ],
            type: "object"
        )
    )]
    public array $operations = [];
}
