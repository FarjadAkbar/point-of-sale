<?php

namespace App\Services;

use App\Models\TaxRate;
use App\Models\Team;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class TaxRateService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(Team $team, array $filters, string $pageName = 'rate_page'): LengthAwarePaginator
    {
        $query = $this->filteredQuery($team, $filters);
        $sort = $filters['sort'] ?? 'created_at';
        $direction = strtolower((string) ($filters['direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy(
            in_array($sort, $this->sortableColumns(), true) ? $sort : 'created_at',
            $direction
        );
        $perPage = min(100, max(10, (int) ($filters['per_page'] ?? 15)));

        return $query->paginate($perPage, ['*'], $pageName)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<TaxRate>
     */
    public function filteredQuery(Team $team, array $filters): Builder
    {
        return TaxRate::query()->forTeam($team)->filter($filters);
    }

    /**
     * @return list<string>
     */
    public function sortableColumns(): array
    {
        return ['id', 'name', 'amount', 'for_tax_group', 'created_at'];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Team $team, array $data): TaxRate
    {
        $data['team_id'] = $team->id;
        $data['for_tax_group'] = (bool) ($data['for_tax_group'] ?? false);

        return TaxRate::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(TaxRate $taxRate, array $data): TaxRate
    {
        $data['for_tax_group'] = (bool) ($data['for_tax_group'] ?? false);
        $taxRate->update($data);

        return $taxRate->fresh();
    }

    public function delete(TaxRate $taxRate): void
    {
        $taxRate->delete();
    }
}
