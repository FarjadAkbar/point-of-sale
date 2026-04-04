<?php

namespace App\Services;

use App\Models\ProductCategory;
use App\Models\Team;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ProductCategoryService
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

        return $query->with(['parent:id,name'])->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<ProductCategory>
     */
    public function filteredQuery(Team $team, array $filters): Builder
    {
        return ProductCategory::query()->forTeam($team)->filter($filters);
    }

    /**
     * @return list<string>
     */
    public function sortableColumns(): array
    {
        return ['id', 'name', 'code', 'is_sub_taxonomy', 'created_at'];
    }

    /**
     * @return Builder<ProductCategory>
     */
    public function parentOptionsQuery(Team $team, ?int $excludeId = null): Builder
    {
        $q = ProductCategory::query()
            ->forTeam($team)
            ->where('is_sub_taxonomy', false)
            ->orderBy('name');

        if ($excludeId !== null) {
            $q->where('id', '!=', $excludeId);
        }

        return $q;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Team $team, array $data): ProductCategory
    {
        $data['team_id'] = $team->id;

        return ProductCategory::query()->create($data)->load('parent');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ProductCategory $productCategory, array $data): ProductCategory
    {
        $productCategory->update($data);

        return $productCategory->fresh(['parent']);
    }

    public function delete(ProductCategory $productCategory): void
    {
        $productCategory->delete();
    }
}
