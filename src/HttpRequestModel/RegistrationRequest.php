<?php

namespace App\HttpRequestModel;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "RegistrationRequest",
    description: "Request body do rejestracji użytkownika",
    type: "object",
    required: ["username", "password"],
    properties: [
        new OA\Property(property: "username", type: "string", example: "admin@motolinker.local"),
        new OA\Property(property: "password", type: "string", example: "superSecretPassword")
    ]
)]
class RegistrationRequest
{
    public string $username;
    public string $password;
}
