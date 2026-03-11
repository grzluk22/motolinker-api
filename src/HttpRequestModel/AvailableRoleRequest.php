<?php

namespace App\HttpRequestModel;

use OpenApi\Attributes as OA;

class AvailableRoleRequest
{
    #[OA\Property(description: "Role name (e.g. ROLE_MODERATOR)", example: "ROLE_MODERATOR")]
    public string $name;

    #[OA\Property(description: "Role description", example: "Can moderate content")]
    public ?string $description = null;
}
