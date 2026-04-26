<?php

namespace App\Services;

use App\Models\Team;
use App\Models\Warranty;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class WarrantyService
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
     * @return Builder<Warranty>
     */
    public function filteredQuery(Team $team, array $filters): Builder
    {
        return Warranty::query()
            ->forTeam($team)
            ->when(
                ! empty($filters['created_by']),
                fn (Builder $query) => $query->where('created_by', (int) $filters['created_by'])
            )
            ->filter($filters);
    }

    /**
     * @return list<string>
     */
    public function sortableColumns(): array
    {
        return ['id', 'name', 'duration_value', 'duration_unit', 'created_at'];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Team $team, array $data, ?int $createdBy = null): Warranty
    {
        $data['team_id'] = $team->id;
        $data['created_by'] = $createdBy;

        return Warranty::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Warranty $warranty, array $data): Warranty
    {
        $warranty->update($data);

        return $warranty->fresh();
    }

    public function delete(Warranty $warranty): void
    {
        $warranty->delete();
    }
}
