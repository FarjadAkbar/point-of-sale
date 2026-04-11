<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['team_id', 'parent_id', 'name'])]
class AccountType extends Model
{
    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<AccountType, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(AccountType::class, 'parent_id');
    }

    /**
     * @return HasMany<AccountType, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(AccountType::class, 'parent_id')->orderBy('name');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForTeam($query, Team $team)
    {
        return $query->where('team_id', $team->id);
    }
}
