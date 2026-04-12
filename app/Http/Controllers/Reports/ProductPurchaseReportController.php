<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\ProductPurchaseReportRequest;
use App\Models\Brand;
use App\Models\BusinessLocation;
use App\Models\Supplier;
use App\Models\Team;
use App\Services\ProductPurchaseReportService;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ProductPurchaseReportController extends Controller
{
    public function __construct(
        protected ProductPurchaseReportService $productPurchaseReportService,
    ) {}

    public function productPurchase(ProductPurchaseReportRequest $request, Team $current_team): Response
    {
        $validated = $request->validated();
        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);
        $locationId = isset($validated['business_location_id'])
            ? (int) $validated['business_location_id']
            : null;
        $supplierId = isset($validated['supplier_id']) ? (int) $validated['supplier_id'] : null;
        $brandId = isset($validated['brand_id']) ? (int) $validated['brand_id'] : null;
        $productId = isset($validated['product_id']) ? (int) $validated['product_id'] : null;
        $searchProduct = $validated['search_product'] ?? null;

        $report = $this->productPurchaseReportService->build(
            $current_team,
            $start,
            $end,
            $locationId,
            $supplierId,
            $brandId,
            $productId,
            $searchProduct,
        );

        $locations = BusinessLocation::query()
            ->forTeam($current_team)
            ->orderBy('name')
            ->get(['id', 'name']);

        $suppliers = Supplier::query()
            ->forTeam($current_team)
            ->orderBy('business_name')
            ->orderBy('first_name')
            ->get(['id', 'supplier_code', 'business_name', 'first_name', 'last_name', 'entity_type'])
            ->map(fn (Supplier $s) => [
                'id' => $s->id,
                'label' => trim($s->display_name.(filled($s->supplier_code) ? ' ('.$s->supplier_code.')' : '')),
            ]);

        $brands = Brand::query()
            ->forTeam($current_team)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('reports/ProductPurchaseReport', [
            'filters' => [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'business_location_id' => $locationId,
                'supplier_id' => $supplierId,
                'brand_id' => $brandId,
                'product_id' => $productId,
                'search_product' => $searchProduct,
            ],
            'businessLocations' => $locations,
            'suppliers' => $suppliers,
            'brands' => $brands,
            'rows' => $report['rows'],
            'footer' => $report['footer'],
        ]);
    }
}
