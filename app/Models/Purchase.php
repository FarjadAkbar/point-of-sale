<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'team_id',
    'supplier_id',
    'business_location_id',
    'ref_no',
    'transaction_date',
    'status',
    'pay_term_number',
    'pay_term_type',
    'discount_type',
    'discount_amount',
    'tax_rate_id',
    'purchase_tax_amount',
    'shipping_details',
    'shipping_charges',
    'additional_expenses',
    'additional_notes',
    'document_path',
    'exchange_rate',
    'lines_total',
    'final_total',
])]
class Purchase extends Model
{
    public function resolveRouteBinding($value, $field = null): ?static
    {
        $team = request()->route('current_team');
        if (! $team instanceof Team) {
            return parent::resolveRouteBinding($value, $field);
        }

        return static::query()
            ->where('team_id', $team->id)
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->firstOrFail();
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return BelongsTo<BusinessLocation, $this>
     */
    public function businessLocation(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class);
    }

    /**
     * @return BelongsTo<TaxRate, $this>
     */
    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }

    /**
     * @return HasMany<PurchaseLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseLine::class);
    }

    /**
     * @return HasMany<PurchasePayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(PurchasePayment::class);
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
            'discount_amount' => 'decimal:4',
            'purchase_tax_amount' => 'decimal:4',
            'shipping_charges' => 'decimal:4',
            'additional_expenses' => 'array',
            'exchange_rate' => 'decimal:6',
            'lines_total' => 'decimal:4',
            'final_total' => 'decimal:4',
        ];
    }
}
