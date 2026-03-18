<?php

namespace App\HttpResponseModel;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "CriterionResponse",
    description: "Odpowiedź zawierająca kryterium z tłumaczeniami",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(
            property: "translations",
            type: "array",
            items: new OA\Items(
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 3),
                    new OA\Property(property: "id_criterion", type: "integer", example: 1),
                    new OA\Property(property: "id_language", type: "integer", example: 6),
                    new OA\Property(property: "name", type: "string", example: "Strona mocowania")
                ]
            )
        )
    ]
)]
class CriterionResponse
{
    public int $id;
    public array $translations;
}
