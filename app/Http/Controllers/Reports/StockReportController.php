<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\StockReportRequest;
use App\Models\Brand;
use App\Models\BusinessLocation;
use App\Models\ProductCategory;
use App\Models\Team;
use App\Models\Unit;
use App\Services\StockReportService;
use Inertia\Inertia;
use Inertia\Response;

class StockReportController extends Controller
{
    public function __construct(
        protected StockReportService $stockReportService,
    ) {}

    public function stockReport(StockReportRequest $request, Team $current_team): Response
    {
        $validated = $request->validated();
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

        $report = $this->stockReportService->build(
            $current_team,
            $locationId,
            $categoryId,
            $subcategoryId,
            $brandId,
            $unitId,
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

        return Inertia::render('reports/StockReport', [
            'filters' => [
                'business_location_id' => $locationId,
                'category_id' => $categoryId,
                'subcategory_id' => $subcategoryId,
                'brand_id' => $brandId,
                'unit_id' => $unitId,
            ],
            'businessLocations' => $locations,
            'categories' => $categories,
            'subcategories' => $subcategories,
            'brands' => $brands,
            'units' => $units,
            'rows' => $report['rows'],
            'summary' => $report['summary'],
            'footer' => $report['footer'],
        ]);
    }
}
