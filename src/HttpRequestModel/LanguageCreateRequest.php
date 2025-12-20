<?php

namespace App\HttpRequestModel;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "LanguageCreateRequest",
    description: "Request body do tworzenia języka",
    type: "object",
    required: ["name", "isoCode"],
    properties: [
        new OA\Property(property: "name", type: "string", example: "Polski"),
        new OA\Property(property: "isoCode", type: "string", example: "pl-PL")
    ]
)]
class LanguageCreateRequest
{
    public string $name;
    public string $isoCode;
}
