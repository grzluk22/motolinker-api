<?php

namespace App\HttpResponseModel;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "SeedSampleDataResponse",
    description: "Odpowiedź po wygenerowaniu przykładowych danych",
    type: "object",
    properties: [
        new OA\Property(property: "message", type: "string", example: "Dodano przykładowe dane."),
        new OA\Property(
            property: "summary",
            type: "object",
            properties: [
                new OA\Property(property: "languages", type: "integer", example: 3),
                new OA\Property(property: "categories", type: "integer", example: 3),
                new OA\Property(property: "category_translations", type: "integer", example: 6),
                new OA\Property(property: "criteria", type: "integer", example: 2),
                new OA\Property(property: "criterion_translations", type: "integer", example: 4),
                new OA\Property(property: "cars", type: "integer", example: 20),
                new OA\Property(property: "articles", type: "integer", example: 30),
                new OA\Property(property: "reference_types", type: "integer", example: 2),
                new OA\Property(property: "references", type: "integer", example: 150)
            ]
        )
    ]
)]
class SeedSampleDataResponse
{
    public string $message;
    public object $summary;
}
