<?php

namespace App\HttpResponseModel;

use OpenApi\Attributes as OA;

class AvailableRoleResponse
{
    #[OA\Property(description: "Role ID")]
    public int $id;

    #[OA\Property(description: "Role name")]
    public string $name;

    #[OA\Property(description: "Role description")]
    public ?string $description;
}
