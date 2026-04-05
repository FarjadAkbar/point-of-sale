<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sale_id',
    'amount',
    'paid_on',
    'method',
    'payment_account_id',
    'note',
    'bank_account_number',
])]
class SalePayment extends Model
{
    /**
     * @return BelongsTo<Sale, $this>
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * @return BelongsTo<PaymentAccount, $this>
     */
    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(PaymentAccount::class);
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'paid_on' => 'datetime',
        ];
    }
}
