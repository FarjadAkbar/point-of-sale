<?php

namespace App\Services;

use App\Models\CustomerGroup;
use App\Models\Team;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class CustomerGroupService
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

        $perPage = (int) ($filters['per_page'] ?? 15);
        $perPage = min(100, max(10, $perPage));

        return $query
            ->with(['sellingPriceGroup:id,name'])
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<CustomerGroup>
     */
    public function filteredQuery(Team $team, array $filters): Builder
    {
        return CustomerGroup::query()
            ->forTeam($team)
            ->filter($filters);
    }

    /**
     * @return list<string>
     */
    public function sortableColumns(): array
    {
        return [
            'id',
            'name',
            'price_calculation_type',
            'amount',
            'created_at',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Team $team, array $data): CustomerGroup
    {
        $data['team_id'] = $team->id;

        return CustomerGroup::query()->create($data)->load('sellingPriceGroup');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(CustomerGroup $customerGroup, array $data): CustomerGroup
    {
        $customerGroup->update($data);

        return $customerGroup->fresh(['sellingPriceGroup']);
    }

    public function delete(CustomerGroup $customerGroup): void
    {
        $customerGroup->delete();
    }
}
