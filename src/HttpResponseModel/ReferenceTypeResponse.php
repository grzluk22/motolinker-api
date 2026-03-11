<?php

namespace App\HttpResponseModel;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ReferenceTypeResponse",
    description: "Odpowiedź zawierająca typ numeru referencyjnego",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "Oryginalne")
    ]
)]
class ReferenceTypeResponse
{
    public int $id;
    public string $name;
}
