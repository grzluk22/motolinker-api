<?php

namespace App\HttpResponseModel;

use OpenApi\Attributes as OA;

class UserResponse
{
    #[OA\Property(description: "User ID")]
    public int $id;

    #[OA\Property(description: "User email")]
    public string $email;

    #[OA\Property(description: "Username")]
    public string $username;

    #[OA\Property(description: "User roles (including inherited)")]
    public array $roles;

    #[OA\Property(description: "List of user group IDs")]
    public array $userGroupIds;
}
