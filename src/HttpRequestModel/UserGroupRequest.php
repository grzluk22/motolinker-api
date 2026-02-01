<?php

namespace App\HttpRequestModel;

use OpenApi\Attributes as OA;

class UserGroupRequest
{
    #[OA\Property(description: "Group name", example: "Editors")]
    public string $name;

    #[OA\Property(description: "Roles for this group", type: "array", items: new OA\Items(type: "string"), example: ["ROLE_EDITOR"])]
    public array $roles = [];

    #[OA\Property(description: "Whether this group is assigned to new users by default", example: false)]
    public bool $isDefault = false;
}
