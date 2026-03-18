<?php

namespace App\HttpResponseModel;

use OpenApi\Attributes as OA;

class PrestashopCategory
{
    public function __construct(
        #[OA\Property(description: 'ID kategorii z Prestashop')]
        public int $id,

        #[OA\Property(description: 'Nazwa kategorii')]
        public string $name,

        #[OA\Property(description: 'ID kategorii nadrzędnej (jeśli istnieje)')]
        public ?int $parentId = null,

        /** @var PrestashopCategory[] */
        #[OA\Property(description: 'Podkategorie', type: 'array', items: new OA\Items(ref: '#/components/schemas/PrestashopCategory'))]
        public array $children = []
    ) {
    }
}
