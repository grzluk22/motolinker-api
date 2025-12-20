<?php

namespace App\HttpRequestModel;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "LanguageUpdateRequest",
    description: "Request body do aktualizacji języka",
    type: "object",
    required: ["id", "name", "isoCode"],
    properties: [
        new OA\Property(property: "id", type: "string", example: "1"),
        new OA\Property(property: "name", type: "string", example: "Polski"),
        new OA\Property(property: "isoCode", type: "string", example: "pl-PL")
    ]
)]
class LanguageUpdateRequest
{
    public string $id;
    public string $name;
    public string $isoCode;
}
