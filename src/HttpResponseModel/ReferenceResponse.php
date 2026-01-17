<?php

namespace App\HttpResponseModel;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ReferenceResponse",
    description: "Odpowiedź zawierająca numer referencyjny",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "id_article", type: "integer", example: 26),
        new OA\Property(property: "type", type: "integer", example: 2),
        new OA\Property(property: "brand", type: "string", example: "BREMBO"),
        new OA\Property(property: "number", type: "string", example: "B156O1")
    ]
)]
class ReferenceResponse
{
    public int $id;
    public int $id_article;
    public int $type;
    public string $brand;
    public string $number;
}
