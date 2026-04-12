<?php

namespace App\Services;

use App\Models\ModifierSet;
use App\Models\Team;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ModifierSetService
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

        return $query->withCount('items')->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<ModifierSet>
     */
    public function filteredQuery(Team $team, array $filters): Builder
    {
        return ModifierSet::query()->forTeam($team)->filter($filters);
    }

    /**
     * @return list<string>
     */
    public function sortableColumns(): array
    {
        return ['id', 'name', 'created_at'];
    }

    /**
     * @param  array{name: string, items: list<array{name: string, price: string|float|int}>}  $data
     */
    public function create(Team $team, array $data): ModifierSet
    {
        $items = $data['items'] ?? [];
        unset($data['items']);
        $data['team_id'] = $team->id;

        return DB::transaction(function () use ($data, $items) {
            $set = ModifierSet::query()->create($data);
            $this->syncItems($set, $items);

            return $set->load('items');
        });
    }

    /**
     * @param  array{name?: string, items?: list<array{name: string, price: string|float|int}>}  $data
     */
    public function update(ModifierSet $modifierSet, array $data): ModifierSet
    {
        $items = $data['items'] ?? null;
        unset($data['items']);

        return DB::transaction(function () use ($modifierSet, $data, $items) {
            if ($data !== []) {
                $modifierSet->update($data);
            }
            if (is_array($items)) {
                $this->syncItems($modifierSet, $items);
            }

            return $modifierSet->fresh(['items']);
        });
    }

    /**
     * @param  list<array{name: string, price: string|float|int}>  $items
     */
    public function syncItems(ModifierSet $modifierSet, array $items): void
    {
        $modifierSet->items()->delete();
        foreach (array_values($items) as $i => $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $modifierSet->items()->create([
                'name' => $name,
                'price' => $row['price'] ?? 0,
                'sort_order' => $i,
            ]);
        }
    }

    public function delete(ModifierSet $modifierSet): void
    {
        $modifierSet->delete();
    }
}
