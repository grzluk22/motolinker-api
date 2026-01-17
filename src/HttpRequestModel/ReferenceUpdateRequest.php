<?php

namespace App\HttpRequestModel;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ReferenceUpdateRequest",
    description: "Request body do aktualizacji numeru referencyjnego",
    type: "object",
    required: ["id", "id_article", "type", "brand", "number"],
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "id_article", type: "integer", example: 26),
        new OA\Property(property: "type", type: "integer", example: 2),
        new OA\Property(property: "brand", type: "string", example: "BREMBO"),
        new OA\Property(property: "number", type: "string", example: "B156O1")
    ]
)]
class ReferenceUpdateRequest
{
    public int $id;
    public int $id_article;
    public int $type;
    public string $brand;
    public string $number;
}
