<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Team;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    public function __construct(
        protected ProductStockService $productStockService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(Team $team, array $filters): LengthAwarePaginator
    {
        $query = $this->filteredQuery($team, $filters);
        $query->withSum('stocks', 'quantity');
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
    /**
     * @param  list<array{business_location_id?: int, quantity?: float|int|string}>  $openingStocks
     */
    public function create(
        Team $team,
        array $data,
        ?UploadedFile $productImage,
        ?UploadedFile $productBrochure,
        array $openingStocks = [],
    ): Product {
        return DB::transaction(function () use ($team, $data, $productImage, $productBrochure, $openingStocks) {
            if ($productImage) {
                $data['image_path'] = $productImage->store('products/images', 'public');
            }
            if ($productBrochure) {
                $data['brochure_path'] = $productBrochure->store('products/brochures', 'public');
            }

            $data['team_id'] = $team->id;

            $product = Product::query()->create($data)->load(['unit', 'brand', 'category']);

            if ($openingStocks !== []) {
                $this->productStockService->setOpeningStocks($product, $openingStocks);
            }

            return $product;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array{business_location_id?: int, quantity?: float|int|string}>  $openingStocks
     */
    public function update(
        Product $product,
        array $data,
        ?UploadedFile $productImage,
        ?UploadedFile $productBrochure,
        array $openingStocks = [],
    ): Product {
        return DB::transaction(function () use ($product, $data, $productImage, $productBrochure, $openingStocks) {
            if ($productImage) {
                if ($product->image_path) {
                    Storage::disk('public')->delete($product->image_path);
                }
                $data['image_path'] = $productImage->store('products/images', 'public');
            }
            if ($productBrochure) {
                if ($product->brochure_path) {
                    Storage::disk('public')->delete($product->brochure_path);
                }
                $data['brochure_path'] = $productBrochure->store('products/brochures', 'public');
            }

            $product->update($data);
            $product->load(['unit', 'brand', 'category']);

            if ($openingStocks !== [] && $product->manage_stock) {
                $this->productStockService->setOpeningStocks($product, $openingStocks);
            }

            return $product;
        });
    }

    public function delete(Product $product): void
    {
        DB::transaction(function () use ($product) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            if ($product->brochure_path) {
                Storage::disk('public')->delete($product->brochure_path);
            }
            $product->delete();
        });
    }

    /**
     * @return Builder<Product>
     */
    public function searchQuery(Team $team, string $term, bool $sellableOnly = false, ?int $businessLocationId = null, ?int $categoryId = null, ?int $brandId = null): Builder
    {
        $term = trim($term);

        $query = Product::query()->forTeam($team);

        if ($term !== '') {
            $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';
            $query->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('sku', 'like', $like);
            });
        }

        if ($categoryId !== null && $categoryId > 0) {
            $query->where('category_id', $categoryId);
        }

        if ($brandId !== null && $brandId > 0) {
            $query->where('brand_id', $brandId);
        }

        if ($sellableOnly) {
            $query->where('not_for_selling', false);
        }

        if ($businessLocationId !== null) {
            $query->forBusinessLocation($businessLocationId);
        }

        $query->with(['category:id,name']);

        if ($businessLocationId !== null) {
            $query->withSum([
                'stocks as location_stock_quantity' => fn ($q) => $q->where('business_location_id', $businessLocationId),
            ], 'quantity');
        }

        $limit = $term === '' ? 200 : 25;

        return $query
            ->orderBy('name')
            ->limit($limit);
    }
}
