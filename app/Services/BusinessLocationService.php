<?php

namespace App\Services;

use App\Models\BusinessLocation;
use App\Models\Team;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class BusinessLocationService
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
     * @return Builder<BusinessLocation>
     */
    public function filteredQuery(Team $team, array $filters): Builder
    {
        return BusinessLocation::query()->forTeam($team)->filter($filters);
    }

    /**
     * @return list<string>
     */
    public function sortableColumns(): array
    {
        return ['id', 'name', 'city', 'created_at'];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Team $team, array $data): BusinessLocation
    {
        $data['team_id'] = $team->id;

        return BusinessLocation::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(BusinessLocation $location, array $data): BusinessLocation
    {
        $location->update($data);

        return $location->fresh();
    }

    public function delete(BusinessLocation $location): void
    {
        $location->delete();
    }
}
