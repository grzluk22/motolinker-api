<?php

namespace App\HttpRequestModel;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ImageUpdateRequest",
    description: "Request body do aktualizacji metadanych obrazu",
    type: "object",
    properties: [
        new OA\Property(property: "position", type: "integer", example: 2)
    ]
)]
class ImageUpdateRequest
{
    public ?int $position = null;
}
