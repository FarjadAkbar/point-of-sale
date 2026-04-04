<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'team_id',
    'name',
    'amount',
    'for_tax_group',
])]
class TaxRate extends Model
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
     * @return BelongsToMany<TaxGroup, $this>
     */
    public function taxGroups(): BelongsToMany
    {
        return $this->belongsToMany(TaxGroup::class, 'tax_group_tax_rate')
            ->withPivot(['position'])
            ->withTimestamps()
            ->orderByPivot('position');
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
                $q->where('name', 'like', $term);
            });
        }

        return $query;
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'for_tax_group' => 'boolean',
        ];
    }
}
