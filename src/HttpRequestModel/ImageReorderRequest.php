<?php

namespace App\HttpRequestModel;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ImageReorderRequest",
    description: "Request body do zmiany kolejności obrazów",
    type: "object",
    required: ["images"],
    properties: [
        new OA\Property(
            property: "images",
            type: "array",
            items: new OA\Items(
                required: ["id", "position"],
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1),
                    new OA\Property(property: "position", type: "integer", example: 1)
                ]
            )
        )
    ]
)]
class ImageReorderRequest
{
    public array $images;
}
