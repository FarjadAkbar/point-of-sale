<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'purchase_id',
    'amount',
    'paid_on',
    'method',
    'payment_account_id',
    'note',
    'bank_account_number',
])]
class PurchasePayment extends Model
{
    /**
     * @return BelongsTo<Purchase, $this>
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
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
