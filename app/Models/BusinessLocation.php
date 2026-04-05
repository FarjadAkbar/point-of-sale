<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'team_id',
    'name',
    'location_id',
    'landmark',
    'city',
    'zip_code',
    'state',
    'country',
    'mobile',
    'alternate_contact_number',
    'email',
    'website',
    'default_selling_price_group_id',
    'featured_product_ids',
])]
class BusinessLocation extends Model
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
     * @return BelongsTo<SellingPriceGroup, $this>
     */
    public function defaultSellingPriceGroup(): BelongsTo
    {
        return $this->belongsTo(SellingPriceGroup::class, 'default_selling_price_group_id');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForTeam($query, Team $team)
    {
        return $query->where('team_id', $team->id);
    }

    /**
     * @param  Builder<static>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<static>
     */
    public function scopeFilter($query, array $filters)
    {
        if (! empty($filters['search'])) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $filters['search']).'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('location_id', 'like', $term)
                    ->orWhere('city', 'like', $term)
                    ->orWhere('landmark', 'like', $term);
            });
        }

        return $query;
    }

    protected function casts(): array
    {
        return [
            'featured_product_ids' => 'array',
        ];
    }
}
