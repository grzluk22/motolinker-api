<?php

namespace App\HttpResponseModel;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "RegistrationResponse",
    description: "Odpowiedź po rejestracji użytkownika",
    type: "object",
    properties: [
        new OA\Property(property: "message", type: "string", example: "Registered Successfully")
    ]
)]
class RegistrationResponse
{
    public string $message;
}
