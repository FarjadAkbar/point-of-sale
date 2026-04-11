<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\TrendingProductsReportRequest;
use App\Models\Brand;
use App\Models\BusinessLocation;
use App\Models\ProductCategory;
use App\Models\Team;
use App\Models\Unit;
use App\Services\TrendingProductsReportService;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class TrendingProductsReportController extends Controller
{
    public function __construct(
        protected TrendingProductsReportService $trendingProductsReportService,
    ) {}

    public function trendingProducts(TrendingProductsReportRequest $request, Team $current_team): Response
    {
        $validated = $request->validated();
        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);
        $limit = (int) $validated['limit'];
        $locationId = isset($validated['business_location_id'])
            ? (int) $validated['business_location_id']
            : null;
        $categoryId = isset($validated['category_id'])
            ? (int) $validated['category_id']
            : null;
        $subcategoryId = isset($validated['subcategory_id'])
            ? (int) $validated['subcategory_id']
            : null;
        $brandId = isset($validated['brand_id'])
            ? (int) $validated['brand_id']
            : null;
        $unitId = isset($validated['unit_id'])
            ? (int) $validated['unit_id']
            : null;
        $productType = (string) ($validated['product_type'] ?? '');

        $chart = $this->trendingProductsReportService->build(
            $current_team,
            $start,
            $end,
            $locationId,
            $categoryId,
            $subcategoryId,
            $brandId,
            $unitId,
            $productType,
            $limit,
        );

        $locations = BusinessLocation::query()
            ->forTeam($current_team)
            ->orderBy('name')
            ->get(['id', 'name']);

        $categories = ProductCategory::query()
            ->forTeam($current_team)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get(['id', 'name']);

        $subcategories = ProductCategory::query()
            ->forTeam($current_team)
            ->whereNotNull('parent_id')
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        $brands = Brand::query()
            ->forTeam($current_team)
            ->orderBy('name')
            ->get(['id', 'name']);

        $units = Unit::query()
            ->forTeam($current_team)
            ->orderBy('name')
            ->get(['id', 'name', 'short_name']);

        return Inertia::render('reports/TrendingProductsReport', [
            'filters' => [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'limit' => $limit,
                'business_location_id' => $locationId,
                'category_id' => $categoryId,
                'subcategory_id' => $subcategoryId,
                'brand_id' => $brandId,
                'unit_id' => $unitId,
                'product_type' => $productType,
            ],
            'businessLocations' => $locations,
            'categories' => $categories,
            'subcategories' => $subcategories,
            'brands' => $brands,
            'units' => $units,
            'chart' => $chart,
        ]);
    }
}
