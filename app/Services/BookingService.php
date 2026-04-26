<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Team;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class BookingService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(Team $team, array $filters, ?int $restrictToUserId = null): LengthAwarePaginator
    {
        $query = $this->filteredQuery($team, $filters, $restrictToUserId);
        $sort = $filters['sort'] ?? 'starts_at';
        $direction = strtolower((string) ($filters['direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy(
            in_array($sort, $this->sortableColumns(), true) ? $sort : 'starts_at',
            $direction
        );
        $perPage = min(100, max(10, (int) ($filters['per_page'] ?? 15)));

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Bookings starting today (local app timezone).
     *
     * @return Collection<int, Booking>
     */
    public function todaysForTeam(Team $team, ?int $restrictToUserId = null): Collection
    {
        return Booking::query()
            ->forTeam($team)
            ->when(
                $restrictToUserId !== null,
                fn (Builder $q) => $q->where(function (Builder $q2) use ($restrictToUserId): void {
                    $q2->where('correspondent_user_id', $restrictToUserId)
                        ->orWhere('service_staff_user_id', $restrictToUserId);
                }),
            )
            ->with([
                'customer:id,entity_type,business_name,first_name,middle_name,last_name,customer_code,mobile',
                'businessLocation:id,name',
                'restaurantTable:id,name',
                'serviceStaff:id,name',
            ])
            ->whereDate('starts_at', now()->toDateString())
            ->orderBy('starts_at')
            ->limit(100)
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Booking>
     */
    public function filteredQuery(Team $team, array $filters, ?int $restrictToUserId = null): Builder
    {
        return Booking::query()
            ->forTeam($team)
            ->when(
                $restrictToUserId !== null,
                fn (Builder $q) => $q->where(function (Builder $q2) use ($restrictToUserId): void {
                    $q2->where('correspondent_user_id', $restrictToUserId)
                        ->orWhere('service_staff_user_id', $restrictToUserId);
                }),
            )
            ->with([
                'customer:id,entity_type,business_name,first_name,middle_name,last_name,customer_code,mobile',
                'businessLocation:id,name',
                'restaurantTable:id,name',
                'correspondent:id,name',
                'serviceStaff:id,name',
            ])
            ->filter($filters);
    }

    /**
     * @return list<string>
     */
    public function sortableColumns(): array
    {
        return ['id', 'starts_at', 'ends_at', 'status', 'created_at'];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Team $team, array $data): Booking
    {
        $data['team_id'] = $team->id;

        return Booking::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Booking $booking, array $data): Booking
    {
        $booking->update($data);

        return $booking->fresh();
    }

    public function delete(Booking $booking): void
    {
        $booking->delete();
    }
}
