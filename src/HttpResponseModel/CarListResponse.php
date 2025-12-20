<?php

namespace App\HttpResponseModel;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "CarListResponse",
    description: "Odpowiedź zawierająca listę samochodów",
    type: "object",
    properties: [
        new OA\Property(
            property: "data",
            type: "array",
            items: new OA\Items(
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1),
                    new OA\Property(property: "manufacturer", type: "string", example: "Opel"),
                    new OA\Property(property: "model", type: "string", example: "Vectra"),
                    new OA\Property(property: "type", type: "string", example: "C"),
                    new OA\Property(property: "model_from", type: "string", example: "2002-09"),
                    new OA\Property(property: "model_to", type: "string", example: "2004-5"),
                    new OA\Property(property: "body_type", type: "string", example: "Sedan"),
                    new OA\Property(property: "drive_type", type: "string", example: "FWD"),
                    new OA\Property(property: "displacement_liters", type: "string", example: "1655"),
                    new OA\Property(property: "displacement_cmm", type: "string", example: "1655"),
                    new OA\Property(property: "fuel_type", type: "string", example: "Gas"),
                    new OA\Property(property: "kw", type: "string", example: "90"),
                    new OA\Property(property: "hp", type: "string", example: "120"),
                    new OA\Property(property: "cylinders", type: "integer", example: 4),
                    new OA\Property(property: "valves", type: "string", example: "8"),
                    new OA\Property(property: "engine_type", type: "string", example: "V2"),
                    new OA\Property(property: "engine_codes", type: "string", example: "KWA456"),
                    new OA\Property(property: "kba", type: "string", example: "45689722")
                ]
            )
        ),
        new OA\Property(property: "total", type: "integer", example: 1)
    ]
)]
class CarListResponse
{
    public array $data;
    public int $total;
}
