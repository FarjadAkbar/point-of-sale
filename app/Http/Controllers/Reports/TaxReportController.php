<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\TaxReportRequest;
use App\Models\BusinessLocation;
use App\Models\Customer;
use App\Models\Team;
use App\Services\TaxReportService;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class TaxReportController extends Controller
{
    public function __construct(
        protected TaxReportService $taxReportService,
    ) {}

    public function taxReport(TaxReportRequest $request, Team $current_team): Response
    {
        $validated = $request->validated();
        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);
        $locationId = isset($validated['business_location_id'])
            ? (int) $validated['business_location_id']
            : null;
        $customerId = isset($validated['customer_id'])
            ? (int) $validated['customer_id']
            : null;

        $report = $this->taxReportService->build($current_team, $start, $end, $locationId, $customerId);

        $locations = BusinessLocation::query()
            ->forTeam($current_team)
            ->orderBy('name')
            ->get(['id', 'name']);

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

        return Inertia::render('reports/TaxReport', [
            'filters' => [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'business_location_id' => $locationId,
                'customer_id' => $customerId,
            ],
            'businessLocations' => $locations,
            'customers' => $customers,
            'taxRates' => $report['tax_rates'],
            'summary' => $report['summary'],
            'inputRows' => $report['input_rows'],
            'outputRows' => $report['output_rows'],
            'expenseRows' => $report['expense_rows'],
            'inputColumnTotals' => $report['input_column_totals'],
            'outputColumnTotals' => $report['output_column_totals'],
            'expenseColumnTotals' => $report['expense_column_totals'],
        ]);
    }
}
