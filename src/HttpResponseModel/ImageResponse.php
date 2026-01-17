<?php

namespace App\HttpResponseModel;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ImageResponse",
    description: "Odpowiedź zawierająca informacje o obrazie",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "id_article", type: "integer", example: 1),
        new OA\Property(property: "position", type: "integer", example: 1),
        new OA\Property(property: "url", type: "string", example: "/uploads/articles/1/abc123.jpg"),
        new OA\Property(property: "thumbnail_url", type: "string", example: "/uploads/articles/1/thumbnails/abc123.jpg"),
        new OA\Property(property: "is_main", type: "boolean", example: true),
        new OA\Property(property: "width", type: "integer", example: 1920),
        new OA\Property(property: "height", type: "integer", example: 1080),
        new OA\Property(property: "file_size", type: "integer", example: 524288)
    ]
)]
class ImageResponse
{
    public int $id;
    public int $id_article;
    public int $position;
    public string $url;
    public ?string $thumbnail_url = null;
    public bool $is_main;
    public int $width;
    public int $height;
    public int $file_size;
}
