<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\ItemsReportRequest;
use App\Models\BusinessLocation;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Team;
use App\Services\ItemsReportService;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ItemsReportController extends Controller
{
    public function __construct(
        protected ItemsReportService $itemsReportService,
    ) {}

    public function itemsReport(ItemsReportRequest $request, Team $current_team): Response
    {
        $validated = $request->validated();
        $locationId = isset($validated['business_location_id'])
            ? (int) $validated['business_location_id']
            : null;
        $supplierId = isset($validated['supplier_id']) ? (int) $validated['supplier_id'] : null;
        $customerId = isset($validated['customer_id']) ? (int) $validated['customer_id'] : null;
        $purchaseStart = isset($validated['purchase_start_date'])
            ? Carbon::parse($validated['purchase_start_date'])
            : null;
        $purchaseEnd = isset($validated['purchase_end_date'])
            ? Carbon::parse($validated['purchase_end_date'])
            : null;
        $sellStart = Carbon::parse($validated['sell_start_date']);
        $sellEnd = Carbon::parse($validated['sell_end_date']);

        $report = $locationId
            ? $this->itemsReportService->build(
                $current_team,
                $locationId,
                $supplierId,
                $customerId,
                $purchaseStart,
                $purchaseEnd,
                $sellStart,
                $sellEnd,
            )
            : [
                'rows' => [],
                'footer' => [
                    'purchase_price' => '0.0000',
                    'sell_quantity' => '0.0000',
                    'selling_price' => '0.0000',
                    'subtotal' => '0.0000',
                ],
            ];

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

        $customers = Customer::query()
            ->forTeam($current_team)
            ->whereIn('party_role', ['customer', 'both'])
            ->orderBy('business_name')
            ->orderBy('first_name')
            ->get(['id', 'customer_code', 'business_name', 'first_name', 'last_name', 'entity_type'])
            ->map(fn (Customer $c) => [
                'id' => $c->id,
                'label' => trim($c->display_name.(filled($c->customer_code) ? ' ('.$c->customer_code.')' : '')),
            ]);

        return Inertia::render('reports/ItemsReport', [
            'filters' => [
                'business_location_id' => $locationId,
                'supplier_id' => $supplierId,
                'customer_id' => $customerId,
                'purchase_start_date' => $purchaseStart?->toDateString(),
                'purchase_end_date' => $purchaseEnd?->toDateString(),
                'sell_start_date' => $sellStart->toDateString(),
                'sell_end_date' => $sellEnd->toDateString(),
            ],
            'businessLocations' => $locations,
            'suppliers' => $suppliers,
            'customers' => $customers,
            'rows' => $report['rows'],
            'footer' => $report['footer'],
        ]);
    }
}
