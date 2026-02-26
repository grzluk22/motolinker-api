<?php

namespace App\HttpRequestModel;

use OpenApi\Attributes as OA;

#[OA\Schema(
    description: "Payload do masowej edycji/zapisu ustawień kafelków dashboardu",
    example: [
        "layout" => [
            [
                "id" => "cars-no-app",
                "x" => 0,
                "y" => 0,
                "rows" => 1,
                "cols" => 1,
                "visible" => true
            ],
            [
                "id" => "demo-chart",
                "x" => 0,
                "y" => 1,
                "rows" => 2,
                "cols" => 2,
                "visible" => true,
                "config" => [
                    "color" => "#ff0000",
                    "dataSource" => "total"
                ]
            ]
        ]
    ]
)]
class DashboardSettingsBulkEditRequest
{
    #[OA\Property(
        description: "Lista układu kafelków",
        type: "array",
        items: new OA\Items(
            properties: [
                new OA\Property(property: "id", type: "string", example: "cars-no-app"),
                new OA\Property(property: "x", type: "integer", example: 0),
                new OA\Property(property: "y", type: "integer", example: 0),
                new OA\Property(property: "rows", type: "integer", example: 1),
                new OA\Property(property: "cols", type: "integer", example: 1),
                new OA\Property(property: "visible", type: "boolean", example: true),
                new OA\Property(
                    property: "config",
                    description: "Dodatkowa konfiguracja kafelka",
                    type: "object",
                    nullable: true,
                    example: ["color" => "#ff0000", "dataSource" => "total"]
                )
            ],
            type: "object"
        )
    )]
    public array $layout = [];
}
