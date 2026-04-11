<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'team_id',
    'business_location_id',
    'expense_category_id',
    'ref_no',
    'transaction_date',
    'expense_for_user_id',
    'contact_id',
    'document_path',
    'tax_rate_id',
    'tax_amount',
    'final_total',
    'additional_notes',
    'is_refund',
    'is_recurring',
    'recur_interval',
    'recur_interval_type',
    'recur_repetitions',
    'subscription_repeat_on',
    'created_by',
])]
class Expense extends Model
{
    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<BusinessLocation, $this>
     */
    public function businessLocation(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class);
    }

    /**
     * @return BelongsTo<ExpenseCategory, $this>
     */
    public function expenseCategory(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    /**
     * @return BelongsTo<TaxRate, $this>
     */
    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function expenseForUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'expense_for_user_id');
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'contact_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<ExpensePayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(ExpensePayment::class);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForTeam($query, Team $team)
    {
        return $query->where('team_id', $team->id);
    }

    protected function casts(): array
    {
        return [
            'transaction_date' => 'datetime',
            'tax_amount' => 'decimal:4',
            'final_total' => 'decimal:4',
            'is_refund' => 'boolean',
            'is_recurring' => 'boolean',
        ];
    }
}
