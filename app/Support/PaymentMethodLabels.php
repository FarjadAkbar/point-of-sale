<?php

namespace App\Support;

use Illuminate\Support\Str;

class PaymentMethodLabels
{
    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $custom = [];
        for ($i = 1; $i <= 7; $i++) {
            $custom['custom_pay_'.$i] = 'Custom payment '.$i;
        }

        return [
            'advance' => 'Advance',
            'cash' => 'Cash',
            'card' => 'Card',
            'cheque' => 'Cheque',
            'bank_transfer' => 'Bank transfer',
            'other' => 'Other',
            'ledger' => 'Ledger',
        ] + $custom;
    }

    public static function label(string $method): string
    {
        return self::options()[$method] ?? Str::headline(str_replace('_', ' ', $method));
    }
}
