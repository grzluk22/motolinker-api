<?php

namespace App\HttpResponseModel;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "CategoryResponse",
    description: "Odpowiedź zawierająca kategorię z tłumaczeniami",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "id_parent", type: "integer", example: 1),
        new OA\Property(
            property: "translations",
            type: "array",
            items: new OA\Items(
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 5),
                    new OA\Property(property: "id_category", type: "integer", example: 3),
                    new OA\Property(property: "id_language", type: "integer", example: 1),
                    new OA\Property(property: "name", type: "string", example: "Układ Hamulcowy 2"),
                    new OA\Property(property: "description", type: "string", example: "Części układu hamulcowego")
                ]
            )
        )
    ]
)]
class CategoryResponse
{
    public int $id;
    public int $id_parent;
    public array $translations;
}
