<?php

namespace App\HttpRequestModel;

use OpenApi\Attributes as OA;

class UserCreateRequest
{
    #[OA\Property(description: "User email", example: "user@example.com")]
    public string $email;

    #[OA\Property(description: "Username", example: "user123")]
    public string $username;

    #[OA\Property(description: "Plain text password", example: "secure_password")]
    public string $password;

    #[OA\Property(description: "User roles", type: "array", items: new OA\Items(type: "string"), example: ["ROLE_USER"])]
    public array $roles = [];

    #[OA\Property(description: "IDs of user groups", type: "array", items: new OA\Items(type: "integer"), example: [1, 2])]
    public array $userGroupIds = [];
}
