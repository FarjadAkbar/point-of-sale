<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sale_id',
    'product_id',
    'quantity',
    'unit_price_before_discount',
    'discount_percent',
    'unit_price_exc_tax',
    'product_tax_percent',
    'line_subtotal_exc_tax',
    'line_tax_amount',
    'line_total',
])]
class SaleLine extends Model
{
    /**
     * @return BelongsTo<Sale, $this>
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price_before_discount' => 'decimal:4',
            'discount_percent' => 'decimal:4',
            'unit_price_exc_tax' => 'decimal:4',
            'product_tax_percent' => 'decimal:4',
            'line_subtotal_exc_tax' => 'decimal:4',
            'line_tax_amount' => 'decimal:4',
            'line_total' => 'decimal:4',
        ];
    }
}
