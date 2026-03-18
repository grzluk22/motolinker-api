<?php

namespace App\HttpResponseModel;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "MessageResponse",
    description: "Odpowiedź zawierająca komunikat",
    type: "object",
    properties: [
        new OA\Property(property: "message", type: "string", example: "Usunięto")
    ]
)]
class MessageResponse
{
    public string $message;
}
