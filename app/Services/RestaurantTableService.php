<?php

namespace App\Services;

use App\Models\RestaurantTable;
use App\Models\Team;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class RestaurantTableService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(Team $team, array $filters): LengthAwarePaginator
    {
        $query = $this->filteredQuery($team, $filters);
        $sort = $filters['sort'] ?? 'created_at';
        $direction = strtolower((string) ($filters['direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy(
            in_array($sort, $this->sortableColumns(), true) ? $sort : 'created_at',
            $direction
        );
        $perPage = min(100, max(10, (int) ($filters['per_page'] ?? 15)));

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<RestaurantTable>
     */
    public function filteredQuery(Team $team, array $filters): Builder
    {
        return RestaurantTable::query()
            ->forTeam($team)
            ->with(['businessLocation:id,name'])
            ->filter($filters);
    }

    /**
     * @return list<string>
     */
    public function sortableColumns(): array
    {
        return ['id', 'name', 'business_location_id', 'created_at'];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Team $team, array $data): RestaurantTable
    {
        $data['team_id'] = $team->id;

        return RestaurantTable::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(RestaurantTable $restaurantTable, array $data): RestaurantTable
    {
        $restaurantTable->update($data);

        return $restaurantTable->fresh();
    }

    public function delete(RestaurantTable $restaurantTable): void
    {
        $restaurantTable->delete();
    }
}
