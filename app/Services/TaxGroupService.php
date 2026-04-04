<?php

namespace App\Services;

use App\Models\TaxGroup;
use App\Models\TaxRate;
use App\Models\Team;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class TaxGroupService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(Team $team, array $filters, string $pageName = 'group_page'): LengthAwarePaginator
    {
        $query = $this->filteredQuery($team, $filters);
        $sort = $filters['sort'] ?? 'created_at';
        $direction = strtolower((string) ($filters['direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy(
            in_array($sort, $this->sortableColumns(), true) ? $sort : 'created_at',
            $direction
        );
        $perPage = min(100, max(10, (int) ($filters['per_page'] ?? 15)));

        return $query->with(['taxRates' => fn ($q) => $q->orderBy('tax_group_tax_rate.position')])
            ->paginate($perPage, ['*'], $pageName)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<TaxGroup>
     */
    public function filteredQuery(Team $team, array $filters): Builder
    {
        return TaxGroup::query()->forTeam($team)->filter($filters);
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
     * @param  list<int>  $taxRateIds
     */
    public function create(Team $team, array $data, array $taxRateIds): TaxGroup
    {
        $data['team_id'] = $team->id;
        $group = TaxGroup::query()->create($data);
        $this->syncRates($team, $group, $taxRateIds);

        return $group->load(['taxRates' => fn ($q) => $q->orderBy('tax_group_tax_rate.position')]);
    }

    /**
     * @param  list<int>  $taxRateIds
     */
    public function update(TaxGroup $taxGroup, array $data, array $taxRateIds): TaxGroup
    {
        $taxGroup->update($data);
        $this->syncRates($taxGroup->team, $taxGroup, $taxRateIds);

        return $taxGroup->fresh()->load(['taxRates' => fn ($q) => $q->orderBy('tax_group_tax_rate.position')]);
    }

    /**
     * @param  list<int>  $taxRateIds
     */
    public function syncRates(Team $team, TaxGroup $group, array $taxRateIds): void
    {
        $validIds = TaxRate::query()
            ->forTeam($team)
            ->whereIn('id', $taxRateIds)
            ->pluck('id')
            ->all();

        $ordered = [];
        $pos = 0;
        foreach ($taxRateIds as $id) {
            $id = (int) $id;
            if (in_array($id, $validIds, true)) {
                $ordered[$id] = ['position' => $pos++];
            }
        }

        $group->taxRates()->sync($ordered);
    }

    public function delete(TaxGroup $taxGroup): void
    {
        $taxGroup->delete();
    }
}
