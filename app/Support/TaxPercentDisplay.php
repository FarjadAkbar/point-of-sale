<?php

namespace App\Support;

use App\Models\TaxRate;

final class TaxPercentDisplay
{
    /**
     * Trim trailing zeros from a percentage amount for display (e.g. 8.0000 → 8, 10.25 stays 10.25).
     */
    public static function amount(string|float|int|null $value): string
    {
        if ($value === null || $value === '') {
            return '0';
        }

        $n = is_numeric($value) ? (float) $value : 0.0;
        $s = rtrim(rtrim(number_format($n, 4, '.', ''), '0'), '.');

        return $s === '' ? '0' : $s;
    }

    /**
     * One line per rate for group "Sub taxes" column: avoid "SGST@8%@8.0000%" when name already encodes the rate.
     */
    public static function rateLine(TaxRate $r): string
    {
        $name = trim($r->name);

        if ($name !== '' && str_contains($name, '@') && str_contains($name, '%')) {
            return $name;
        }

        $pct = self::amount($r->amount);

        return $name === '' ? "{$pct}%" : "{$name} ({$pct}%)";
    }
}
