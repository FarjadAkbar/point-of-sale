<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'team_id',
    'name',
    'sku',
    'barcode_type',
    'unit_id',
    'brand_id',
    'category_id',
    'subcategory_id',
    'business_location_ids',
    'manage_stock',
    'alert_quantity',
    'description',
    'image_path',
    'brochure_path',
    'enable_imei_serial',
    'not_for_selling',
    'weight',
    'preparation_time_minutes',
    'application_tax',
    'selling_price_tax_type',
    'product_type',
    'single_dpp',
    'single_dpp_inc_tax',
    'profit_percent',
    'single_dsp',
    'single_dsp_inc_tax',
    'combo_profit_percent',
    'combo_selling_price',
    'combo_selling_price_inc_tax',
    'combo_lines',
    'combo_purchase_total_exc_tax',
    'combo_purchase_total_inc_tax',
    'variation_sku_format',
    'variation_matrix',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

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
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return BelongsTo<ProductCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    /**
     * @return BelongsTo<ProductCategory, $this>
     */
    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'subcategory_id');
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeForTeam($query, Team $team)
    {
        return $query->where('team_id', $team->id);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @param  array<string, mixed>  $filters
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeFilter($query, array $filters)
    {
        if (! empty($filters['search'])) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $filters['search']).'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('sku', 'like', $term);
            });
        }

        if (! empty($filters['product_type'])) {
            $query->where('product_type', $filters['product_type']);
        }

        return $query;
    }

    protected function casts(): array
    {
        return [
            'business_location_ids' => 'array',
            'manage_stock' => 'boolean',
            'alert_quantity' => 'decimal:4',
            'enable_imei_serial' => 'boolean',
            'not_for_selling' => 'boolean',
            'weight' => 'decimal:4',
            'single_dpp' => 'decimal:4',
            'single_dpp_inc_tax' => 'decimal:4',
            'profit_percent' => 'decimal:4',
            'single_dsp' => 'decimal:4',
            'single_dsp_inc_tax' => 'decimal:4',
            'combo_profit_percent' => 'decimal:4',
            'combo_selling_price' => 'decimal:4',
            'combo_selling_price_inc_tax' => 'decimal:4',
            'combo_lines' => 'array',
            'combo_purchase_total_exc_tax' => 'decimal:4',
            'combo_purchase_total_inc_tax' => 'decimal:4',
            'variation_matrix' => 'array',
        ];
    }
}
