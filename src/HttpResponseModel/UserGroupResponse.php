<?php

namespace App\HttpResponseModel;

use OpenApi\Attributes as OA;

class UserGroupResponse
{
    #[OA\Property(description: "ID of the group")]
    public int $id;

    #[OA\Property(description: "Group name")]
    public string $name;

    #[OA\Property(description: "Roles for this group", type: "array", items: new OA\Items(type: "string"))]
    public array $roles = [];

    #[OA\Property(description: "Whether this group is assigned to new users by default")]
    public bool $isDefault;
}
