<?php

namespace App\HttpRequestModel;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "CategoryCreateRequest",
    description: "Request body do tworzenia kategorii",
    type: "object",
    required: ["id_parent", "translations"],
    properties: [
        new OA\Property(property: "id_parent", type: "integer", example: 1),
        new OA\Property(
            property: "translations",
            type: "array",
            items: new OA\Items(
                required: ["id_language", "name", "description"],
                properties: [
                    new OA\Property(property: "id_language", type: "integer", example: 1),
                    new OA\Property(property: "name", type: "string", example: "Układ Hamulcowy 2"),
                    new OA\Property(property: "description", type: "string", example: "Części układu hamulcowego")
                ]
            )
        )
    ]
)]
class CategoryCreateRequest
{
    public int $id_parent;
    public array $translations;
}
