<?php

namespace App\Services;

use App\Models\SellingPriceGroup;
use App\Models\Team;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class SellingPriceGroupService
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
     * @return Builder<SellingPriceGroup>
     */
    public function filteredQuery(Team $team, array $filters): Builder
    {
        return SellingPriceGroup::query()->forTeam($team)->filter($filters);
    }

    /**
     * @return list<string>
     */
    public function sortableColumns(): array
    {
        return ['id', 'name', 'created_at'];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Team $team, array $data): SellingPriceGroup
    {
        $data['team_id'] = $team->id;

        return SellingPriceGroup::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SellingPriceGroup $group, array $data): SellingPriceGroup
    {
        $group->update($data);

        return $group->fresh();
    }

    public function delete(SellingPriceGroup $group): void
    {
        $group->delete();
    }
}
