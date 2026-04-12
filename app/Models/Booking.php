<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'team_id',
    'business_location_id',
    'customer_id',
    'correspondent_user_id',
    'restaurant_table_id',
    'service_staff_user_id',
    'starts_at',
    'ends_at',
    'customer_note',
    'send_notification',
    'status',
])]
class Booking extends Model
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
     * @return BelongsTo<BusinessLocation, $this>
     */
    public function businessLocation(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function correspondent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'correspondent_user_id');
    }

    /**
     * @return BelongsTo<RestaurantTable, $this>
     */
    public function restaurantTable(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function serviceStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'service_staff_user_id');
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
                $q->where('customer_note', 'like', $term)
                    ->orWhereHas('customer', function ($cq) use ($term) {
                        $cq->where('customer_code', 'like', $term)
                            ->orWhere('business_name', 'like', $term)
                            ->orWhere('first_name', 'like', $term)
                            ->orWhere('last_name', 'like', $term)
                            ->orWhere('mobile', 'like', $term);
                    })
                    ->orWhereHas('businessLocation', fn ($lq) => $lq->where('name', 'like', $term))
                    ->orWhereHas('restaurantTable', fn ($tq) => $tq->where('name', 'like', $term));
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'send_notification' => 'boolean',
            'status' => BookingStatus::class,
        ];
    }
}
