<?php

namespace App\Models;

use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'team_id',
    'party_role',
    'entity_type',
    'customer_code',
    'customer_group_id',
    'business_name',
    'prefix',
    'first_name',
    'middle_name',
    'last_name',
    'mobile',
    'alternate_number',
    'landline',
    'email',
    'dob',
    'tax_number',
    'opening_balance',
    'credit_limit',
    'pay_term_number',
    'pay_term_type',
    'address_line_1',
    'address_line_2',
    'city',
    'state',
    'country',
    'zip_code',
    'land_mark',
    'street_name',
    'building_number',
    'additional_number',
    'shipping_address',
    'custom_field1',
    'custom_field2',
    'custom_field3',
    'custom_field4',
    'custom_field5',
    'custom_field6',
    'custom_field7',
    'custom_field8',
    'custom_field9',
    'custom_field10',
])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, SoftDeletes;

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
     * @return BelongsTo<CustomerGroup, $this>
     */
    public function customerGroup(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class);
    }

    /**
     * @return HasMany<CustomerContactPerson, $this>
     */
    public function contactPersons(): HasMany
    {
        return $this->hasMany(CustomerContactPerson::class)->orderBy('position');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'customer_user')
            ->withTimestamps();
    }

    /**
     * @return HasMany<Sale, $this>
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->entity_type === 'business' && filled($this->business_name)) {
            return $this->business_name;
        }

        $parts = array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ]);

        return implode(' ', $parts) ?: ($this->customer_code ?? '—');
    }

    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'opening_balance' => 'decimal:2',
            'credit_limit' => 'decimal:2',
        ];
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
        $partyRole = $filters['party_role'] ?? null;
        if ($partyRole === 'all') {
            // no party filter
        } elseif (filled($partyRole)) {
            $query->where('party_role', $partyRole);
        } else {
            $query->whereIn('party_role', ['customer', 'both']);
        }

        if (! empty($filters['search'])) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $filters['search']).'%';
            $query->where(function ($q) use ($term) {
                $q->where('customer_code', 'like', $term)
                    ->orWhere('business_name', 'like', $term)
                    ->orWhere('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('mobile', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('city', 'like', $term);
            });
        }

        if (! empty($filters['entity_type'])) {
            $query->where('entity_type', $filters['entity_type']);
        }

        return $query;
    }
}
