<?php

namespace App\Support;

class ProductOptionLists
{
    /**
     * @return list<array{value: string, label: string}>
     */
    public static function barcodeTypes(): array
    {
        return [
            ['value' => 'code128', 'label' => 'Code 128'],
            ['value' => 'ean13', 'label' => 'EAN-13'],
            ['value' => 'upca', 'label' => 'UPC-A'],
            ['value' => 'qr', 'label' => 'QR code'],
            ['value' => 'none', 'label' => 'None'],
        ];
    }

    /**
     * @return list<array{id: string, label: string, rate: float}>
     */
    public static function taxOptions(): array
    {
        return [
            ['id' => 'none', 'label' => 'No tax', 'rate' => 0.0],
            ['id' => 'std_15', 'label' => 'Standard 15%', 'rate' => 15.0],
            ['id' => 'reduced_10', 'label' => 'Reduced 10%', 'rate' => 10.0],
        ];
    }
}
