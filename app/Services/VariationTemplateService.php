<?php

namespace App\Services;

use App\Models\Team;
use App\Models\VariationTemplate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class VariationTemplateService
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

        return $query->withCount('values')->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<VariationTemplate>
     */
    public function filteredQuery(Team $team, array $filters): Builder
    {
        return VariationTemplate::query()->forTeam($team)->filter($filters);
    }

    /**
     * @return list<string>
     */
    public function sortableColumns(): array
    {
        return ['id', 'name', 'created_at'];
    }

    /**
     * @param  array{name: string, values?: list<string>}  $data
     */
    public function create(Team $team, array $data): VariationTemplate
    {
        $values = $data['values'] ?? [];
        unset($data['values']);
        $data['team_id'] = $team->id;

        return DB::transaction(function () use ($data, $values) {
            $template = VariationTemplate::query()->create($data);
            $this->syncValues($template, $values);

            return $template->load('values');
        });
    }

    /**
     * @param  array{name?: string, values?: list<string>}  $data
     */
    public function update(VariationTemplate $template, array $data): VariationTemplate
    {
        $values = $data['values'] ?? null;
        unset($data['values']);

        return DB::transaction(function () use ($template, $data, $values) {
            if ($data !== []) {
                $template->update($data);
            }
            if (is_array($values)) {
                $this->syncValues($template, $values);
            }

            return $template->fresh(['values']);
        });
    }

    /**
     * @param  list<string>  $values
     */
    public function syncValues(VariationTemplate $template, array $values): void
    {
        $template->values()->delete();
        foreach (array_values($values) as $i => $value) {
            $v = trim((string) $value);
            if ($v === '') {
                continue;
            }
            $template->values()->create([
                'value' => $v,
                'sort_order' => $i,
            ]);
        }
    }

    public function delete(VariationTemplate $template): void
    {
        $template->delete();
    }
}
