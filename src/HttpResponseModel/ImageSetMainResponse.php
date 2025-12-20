<?php

namespace App\HttpResponseModel;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ImageSetMainResponse",
    description: "Odpowiedź po ustawieniu zdjęcia jako głównego",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "is_main", type: "boolean", example: true)
    ]
)]
class ImageSetMainResponse
{
    public int $id;
    public bool $is_main;
}
