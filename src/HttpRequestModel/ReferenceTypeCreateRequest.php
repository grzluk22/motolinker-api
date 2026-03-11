<?php

namespace App\HttpRequestModel;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ReferenceTypeCreateRequest",
    description: "Request body do tworzenia typu numeru referencyjnego",
    type: "object",
    required: ["name"],
    properties: [
        new OA\Property(property: "name", type: "string", example: "Oryginalny")
    ]
)]
class ReferenceTypeCreateRequest
{
    public string $name;
}
