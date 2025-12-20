<?php

namespace App\HttpRequestModel;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "CarUpdateRequest",
    description: "Request body do aktualizacji samochodu",
    type: "object",
    required: ["id", "manufacturer", "model", "type", "model_from", "model_to", "body_type", "drive_type", "displacement_liters", "displacement_cmm", "fuel_type", "kw", "hp", "cylinders", "valves", "engine_type", "engine_codes", "kba"],
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
)]
class CarUpdateRequest
{
    public int $id;
    public string $manufacturer;
    public string $model;
    public string $type;
    public string $model_from;
    public string $model_to;
    public string $body_type;
    public string $drive_type;
    public string $displacement_liters;
    public string $displacement_cmm;
    public string $fuel_type;
    public string $kw;
    public string $hp;
    public int $cylinders;
    public string $valves;
    public string $engine_type;
    public string $engine_codes;
    public string $kba;
}
