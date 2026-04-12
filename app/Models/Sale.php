<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'team_id',
    'created_by',
    'sales_commission_agent_id',
    'customer_id',
    'business_location_id',
    'selling_price_group_id',
    'restaurant_table_id',
    'invoice_no',
    'transaction_date',
    'status',
    'pay_term_number',
    'pay_term_type',
    'discount_type',
    'discount_amount',
    'tax_rate_id',
    'sale_tax_amount',
    'shipping_details',
    'shipping_charges',
    'shipping_address',
    'shipping_status',
    'delivered_to',
    'delivery_person',
    'shipping_customer_note',
    'shipping_document_path',
    'additional_expenses',
    'sale_note',
    'document_path',
    'lines_total',
    'final_total',
])]
class Sale extends Model
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
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<SalesCommissionAgent, $this>
     */
    public function commissionAgent(): BelongsTo
    {
        return $this->belongsTo(SalesCommissionAgent::class, 'sales_commission_agent_id');
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<BusinessLocation, $this>
     */
    public function businessLocation(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class);
    }

    /**
     * @return BelongsTo<SellingPriceGroup, $this>
     */
    public function sellingPriceGroup(): BelongsTo
    {
        return $this->belongsTo(SellingPriceGroup::class);
    }

    /**
     * @return BelongsTo<RestaurantTable, $this>
     */
    public function restaurantTable(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class);
    }

    /**
     * @return BelongsTo<TaxRate, $this>
     */
    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }

    /**
     * @return HasMany<SaleLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(SaleLine::class);
    }

    /**
     * @return HasMany<SalePayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    /**
     * @return HasMany<SaleActivity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(SaleActivity::class)->orderByDesc('created_at');
    }

    /**
     * @return HasMany<SaleReturn, $this>
     */
    public function saleReturns(): HasMany
    {
        return $this->hasMany(SaleReturn::class, 'parent_sale_id');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForTeam($query, Team $team)
    {
        return $query->where($query->qualifyColumn('team_id'), $team->id);
    }

    protected function casts(): array
    {
        return [
            'transaction_date' => 'datetime',
            'discount_amount' => 'decimal:4',
            'sale_tax_amount' => 'decimal:4',
            'shipping_charges' => 'decimal:4',
            'additional_expenses' => 'array',
            'lines_total' => 'decimal:4',
            'final_total' => 'decimal:4',
        ];
    }
}
