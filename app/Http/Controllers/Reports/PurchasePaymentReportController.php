<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\PurchasePaymentReportRequest;
use App\Models\BusinessLocation;
use App\Models\Supplier;
use App\Models\Team;
use App\Services\PurchasePaymentReportService;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class PurchasePaymentReportController extends Controller
{
    public function __construct(
        protected PurchasePaymentReportService $purchasePaymentReportService,
    ) {}

    public function purchasePayments(PurchasePaymentReportRequest $request, Team $current_team): Response
    {
        $validated = $request->validated();
        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);
        $locationId = isset($validated['business_location_id'])
            ? (int) $validated['business_location_id']
            : null;
        $supplierId = isset($validated['supplier_id'])
            ? (int) $validated['supplier_id']
            : null;

        $report = $this->purchasePaymentReportService->build(
            $current_team,
            $start,
            $end,
            $locationId,
            $supplierId,
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

        return Inertia::render('reports/PurchasePaymentReport', [
            'filters' => [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'business_location_id' => $locationId,
                'supplier_id' => $supplierId,
            ],
            'businessLocations' => $locations,
            'suppliers' => $suppliers,
            'rows' => $report['rows'],
            'footer' => $report['footer'],
        ]);
    }
}
