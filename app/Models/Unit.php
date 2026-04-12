<?php

namespace App\Models;

use Database\Factories\UnitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'team_id',
    'name',
    'short_name',
    'allow_decimal',
    'is_multiple_of_base',
    'base_unit_id',
    'base_unit_multiplier',
])]
class Unit extends Model
{
    /** @use HasFactory<UnitFactory> */
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
    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    /**
     * @return HasMany<Unit, $this>
     */
    public function derivedUnits(): HasMany
    {
        return $this->hasMany(Unit::class, 'base_unit_id');
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
                    ->orWhere('short_name', 'like', $term);
            });
        }

        return $query;
    }

    protected function casts(): array
    {
        return [
            'allow_decimal' => 'boolean',
            'is_multiple_of_base' => 'boolean',
            'base_unit_multiplier' => 'decimal:6',
        ];
    }
}
