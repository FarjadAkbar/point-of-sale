<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Team;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;

class ProductService
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

        return $query
            ->with(['unit:id,name,short_name', 'brand:id,name', 'category:id,name'])
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Product>
     */
    public function filteredQuery(Team $team, array $filters): Builder
    {
        return Product::query()->forTeam($team)->filter($filters);
    }

    /**
     * @return list<string>
     */
    public function sortableColumns(): array
    {
        return ['id', 'name', 'sku', 'product_type', 'created_at'];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(
        Team $team,
        array $data,
        ?UploadedFile $productImage,
        ?UploadedFile $productBrochure
    ): Product {
        if ($productImage) {
            $data['image_path'] = $productImage->store('products/images', 'public');
        }
        if ($productBrochure) {
            $data['brochure_path'] = $productBrochure->store('products/brochures', 'public');
        }

        $data['team_id'] = $team->id;

        return Product::query()->create($data)->load(['unit', 'brand', 'category']);
    }

    /**
     * @return Builder<Product>
     */
    public function searchQuery(Team $team, string $term): Builder
    {
        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';

        return Product::query()
            ->forTeam($team)
            ->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('sku', 'like', $like);
            })
            ->orderBy('name')
            ->limit(25);
    }
}
