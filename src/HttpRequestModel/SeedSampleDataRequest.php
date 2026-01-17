<?php

namespace App\HttpRequestModel;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "SeedSampleDataRequest",
    description: "Request body do generowania przykładowych danych",
    type: "object",
    properties: [
        new OA\Property(property: "numCars", type: "integer", example: 20, description: "Liczba samochodów do wygenerowania (domyślnie: 20)"),
        new OA\Property(property: "numArticles", type: "integer", example: 30, description: "Liczba artykułów do wygenerowania (domyślnie: 30)")
    ]
)]
class SeedSampleDataRequest
{
    public ?int $numCars = null;
    public ?int $numArticles = null;
}
