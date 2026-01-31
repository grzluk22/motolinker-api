<?php

namespace App\HttpRequestModel;

use OpenApi\Attributes as OA;

class UserUpdateRequest
{
    #[OA\Property(description: "User email", example: "user@example.com")]
    public ?string $email = null;

    #[OA\Property(description: "Username", example: "user123")]
    public ?string $username = null;

    #[OA\Property(description: "Plain text password (optional)", example: "new_secure_password")]
    public ?string $password = null;

    #[OA\Property(description: "User roles", type: "array", items: new OA\Items(type: "string"), example: ["ROLE_USER"])]
    public ?array $roles = null;

    #[OA\Property(description: "IDs of user groups", type: "array", items: new OA\Items(type: "integer"), example: [1, 2])]
    public ?array $userGroupIds = null;
}
