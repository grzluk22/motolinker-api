<?php

namespace App\Service;

class SchemaProviderService
{
    /**
     * Zwraca listę dostępnych zdefiniowanych pól dla mapowania na froncie.
     * Te pola będą używane do połączenia z kluczami zewnętrznych systemów.
     * 
     * @return array
     */
    public function getAvailableLocalFields(): array
    {
        return [
            ['id' => 'name', 'label' => 'Nazwa produktu'],
            ['id' => 'sku', 'label' => 'SKU (Nr referencyjny)'],
            ['id' => 'ean13', 'label' => 'EAN13'],
            ['id' => 'weight', 'label' => 'Waga'],
            ['id' => 'price_net', 'label' => 'Cena netto'],
            ['id' => 'description', 'label' => 'Opis produktu'],
            ['id' => 'active', 'label' => 'Aktywny'],
            ['id' => 'quantity', 'label' => 'Ilość / Stan na magazynie']
        ];
    }
}
