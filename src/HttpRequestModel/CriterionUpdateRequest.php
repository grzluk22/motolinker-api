<?php

namespace App\HttpRequestModel;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "CriterionUpdateRequest",
    description: "Request body do aktualizacji kryterium",
    type: "object",
    required: ["id", "translations"],
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(
            property: "translations",
            type: "array",
            items: new OA\Items(
                properties: [
                    new OA\Property(property: "id", type: "integer", nullable: true, example: 3),
                    new OA\Property(property: "id_criterion", type: "integer", example: 1),
                    new OA\Property(property: "id_language", type: "integer", example: 6),
                    new OA\Property(property: "name", type: "string", example: "Strona mocowania")
                ]
            )
        )
    ]
)]
class CriterionUpdateRequest
{
    public int $id;
    public array $translations;
}
