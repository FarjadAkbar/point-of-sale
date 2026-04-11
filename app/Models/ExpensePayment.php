<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'expense_id',
    'amount',
    'paid_on',
    'method',
    'payment_account_id',
    'note',
    'bank_account_number',
])]
class ExpensePayment extends Model
{
    /**
     * @return BelongsTo<Expense, $this>
     */
    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
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
