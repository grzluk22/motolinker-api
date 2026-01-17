<?php

namespace App\HttpResponseModel;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "StockResponse",
    description: "Odpowiedź zawierająca magazyn",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "Antwerpia")
    ]
)]
class StockResponse
{
    public int $id;
    public string $name;
}
