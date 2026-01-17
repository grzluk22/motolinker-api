<?php

namespace App\HttpRequestModel;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ReferenceTypeUpdateRequest",
    description: "Request body do aktualizacji typu numeru referencyjnego",
    type: "object",
    required: ["id", "name"],
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "Oryginalny")
    ]
)]
class ReferenceTypeUpdateRequest
{
    public int $id;
    public string $name;
}
