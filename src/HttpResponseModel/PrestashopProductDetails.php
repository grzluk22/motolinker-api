<?php

declare(strict_types=1);

namespace App\HttpResponseModel;

use OpenApi\Attributes as OA;

class PrestashopProductDetails extends PrestashopProduct
{
    /**
     * @param string[] $images
     * @param array<string, mixed> $rawDetails
     */
    public function __construct(
        ?int $id = null,
        ?string $name = null,
        ?string $price = null,
        ?string $description = null,
        ?string $reference = null,
        ?string $active = null,

        #[OA\Property(description: 'URL-e zdjęć produktu do pobrania wynikające z API', type: 'array', items: new OA\Items(type: 'string'))]
        public array $images = [],

        #[OA\Property(description: 'Wszystkie dane pobrane prosto z Prestashop Webservice (pełne szczegóły)', type: 'object')]
        public array $rawDetails = []
    ) {
        parent::__construct(
            id: $id,
            reference: $reference,
            price: $price,
            active: $active,
            name: $name,
            description: $description
        );
    }
}
