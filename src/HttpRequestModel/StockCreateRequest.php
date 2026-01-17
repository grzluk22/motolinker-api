<?php

namespace App\HttpRequestModel;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "StockCreateRequest",
    description: "Request body do tworzenia magazynu",
    type: "object",
    required: ["name"],
    properties: [
        new OA\Property(property: "name", type: "string", example: "Antwerpia")
    ]
)]
class StockCreateRequest
{
    public string $name;
}
