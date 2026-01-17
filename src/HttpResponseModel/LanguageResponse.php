<?php

namespace App\HttpResponseModel;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "LanguageResponse",
    description: "Odpowiedź zawierająca język",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "string", example: "1"),
        new OA\Property(property: "name", type: "string", example: "Polski"),
        new OA\Property(property: "isoCode", type: "string", example: "pl-PL")
    ]
)]
class LanguageResponse
{
    public string $id;
    public string $name;
    public string $isoCode;
}
