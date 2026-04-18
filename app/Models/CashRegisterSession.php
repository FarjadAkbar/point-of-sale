<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'team_id',
    'business_location_id',
    'user_id',
    'status',
    'opened_at',
    'closed_at',
    'opening_cash',
    'total_card_slips',
    'total_cheque',
    'total_cash',
    'total_bank_transfer',
    'total_advance_payment',
    'custom_pay_1',
    'custom_pay_2',
    'custom_pay_3',
    'custom_pay_4',
    'custom_pay_5',
    'custom_pay_6',
    'custom_pay_7',
    'other_payments',
    'total',
])]
class CashRegisterSession extends Model
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForTeam($query, Team $team)
    {
        return $query->where($query->qualifyColumn('team_id'), $team->id);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOpen($query)
    {
        return $query->where($query->qualifyColumn('status'), 'open');
    }

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'opening_cash' => 'decimal:4',
            'total_card_slips' => 'decimal:4',
            'total_cheque' => 'decimal:4',
            'total_cash' => 'decimal:4',
            'total_bank_transfer' => 'decimal:4',
            'total_advance_payment' => 'decimal:4',
            'custom_pay_1' => 'decimal:4',
            'custom_pay_2' => 'decimal:4',
            'custom_pay_3' => 'decimal:4',
            'custom_pay_4' => 'decimal:4',
            'custom_pay_5' => 'decimal:4',
            'custom_pay_6' => 'decimal:4',
            'custom_pay_7' => 'decimal:4',
            'other_payments' => 'decimal:4',
            'total' => 'decimal:4',
        ];
    }
}
