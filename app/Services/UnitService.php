<?php

namespace App\Services;

use App\Models\Team;
use App\Models\Unit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class UnitService
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

        return $query->with(['baseUnit:id,name,short_name'])->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Unit>
     */
    public function filteredQuery(Team $team, array $filters): Builder
    {
        return Unit::query()
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
        return ['id', 'name', 'short_name', 'allow_decimal', 'is_multiple_of_base', 'created_at'];
    }

    /**
     * @return Builder<Unit>
     */
    public function baseUnitOptionsQuery(Team $team, ?int $excludeId = null): Builder
    {
        $q = Unit::query()->forTeam($team)->orderBy('name');
        if ($excludeId) {
            $q->where('id', '!=', $excludeId);
        }

        return $q;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Team $team, array $data, ?int $createdBy = null): Unit
    {
        $data['team_id'] = $team->id;
        $data['created_by'] = $createdBy;

        return Unit::query()->create($data)->load('baseUnit');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Unit $unit, array $data): Unit
    {
        $unit->update($data);

        return $unit->fresh(['baseUnit']);
    }

    public function delete(Unit $unit): void
    {
        $unit->delete();
    }
}
