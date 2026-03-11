<?php

namespace App\HttpResponseModel;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "CarDeleteResponse",
    description: "Odpowiedź po usunięciu samochodu",
    type: "object",
    properties: [
        new OA\Property(property: "message", type: "string", example: "Usunięto samochód oraz odpięto od 5 produktów."),
        new OA\Property(
            property: "deletedItem",
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(property: "manufacturer", type: "string", example: "Opel"),
                new OA\Property(property: "model", type: "string", example: "Vectra")
            ]
        )
    ]
)]
class CarDeleteResponse
{
    public string $message;
    public object $deletedItem;
}
