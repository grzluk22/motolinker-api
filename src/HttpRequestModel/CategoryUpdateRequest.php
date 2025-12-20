<?php

namespace App\HttpRequestModel;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "CategoryUpdateRequest",
    description: "Request body do aktualizacji kategorii",
    type: "object",
    required: ["id", "id_parent", "translations"],
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "id_parent", type: "integer", example: 1),
        new OA\Property(
            property: "translations",
            type: "array",
            items: new OA\Items(
                properties: [
                    new OA\Property(property: "id", type: "integer", nullable: true, example: 5),
                    new OA\Property(property: "id_category", type: "integer", example: 3),
                    new OA\Property(property: "id_language", type: "integer", example: 1),
                    new OA\Property(property: "name", type: "string", example: "Układ Hamulcowy 2"),
                    new OA\Property(property: "description", type: "string", example: "Części układu hamulcowego")
                ]
            )
        )
    ]
)]
class CategoryUpdateRequest
{
    public int $id;
    public int $id_parent;
    public array $translations;
}
