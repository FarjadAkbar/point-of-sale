<?php

namespace App\Services;

use App\Models\SalesCommissionAgent;
use App\Models\Team;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class SalesCommissionAgentService
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
     * @return Builder<SalesCommissionAgent>
     */
    public function filteredQuery(Team $team, array $filters): Builder
    {
        return SalesCommissionAgent::query()->forTeam($team)->filter($filters);
    }

    /**
     * @return list<string>
     */
    public function sortableColumns(): array
    {
        return ['id', 'first_name', 'last_name', 'email', 'cmmsn_percent', 'created_at'];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Team $team, array $data): SalesCommissionAgent
    {
        $data['team_id'] = $team->id;

        return SalesCommissionAgent::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SalesCommissionAgent $agent, array $data): SalesCommissionAgent
    {
        $agent->update($data);

        return $agent->fresh();
    }

    public function delete(SalesCommissionAgent $agent): void
    {
        $agent->delete();
    }
}
