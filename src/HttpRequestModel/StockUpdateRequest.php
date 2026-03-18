<?php

namespace App\HttpRequestModel;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "StockUpdateRequest",
    description: "Request body do aktualizacji magazynu",
    type: "object",
    required: ["id", "name"],
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "Antwerpia")
    ]
)]
class StockUpdateRequest
{
    public int $id;
    public string $name;
}
