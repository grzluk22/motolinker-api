<?php

namespace App\HttpRequestModel;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "CriterionCreateRequest",
    description: "Request body do tworzenia kryterium",
    type: "object",
    required: ["translations"],
    properties: [
        new OA\Property(
            property: "translations",
            type: "array",
            items: new OA\Items(
                required: ["id_language", "name"],
                properties: [
                    new OA\Property(property: "id_language", type: "integer", example: 6),
                    new OA\Property(property: "name", type: "string", example: "Strona mocowania")
                ]
            )
        )
    ]
)]
class CriterionCreateRequest
{
    public array $translations;
}
