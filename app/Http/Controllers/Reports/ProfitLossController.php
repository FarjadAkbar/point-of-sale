<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\ProfitLossReportRequest;
use App\Models\BusinessLocation;
use App\Models\Team;
use App\Services\ProfitLossReportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProfitLossController extends Controller
{
    public function __construct(
        protected ProfitLossReportService $profitLossReportService,
    ) {}

    public function profitLoss(ProfitLossReportRequest $request, Team $current_team): Response
    {
        $validated = $request->validated();
        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);
        $locationId = isset($validated['business_location_id'])
            ? (int) $validated['business_location_id']
            : null;

        $report = $this->profitLossReportService->build($current_team, $start, $end, $locationId);

        $locations = BusinessLocation::query()
            ->forTeam($current_team)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('reports/ProfitLoss', [
            'filters' => [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'business_location_id' => $locationId,
            ],
            'businessLocations' => $locations,
            'summary' => $report['summary'],
            'profitByProducts' => $report['profit_by_products'],
            'profitByCategories' => $report['profit_by_categories'],
            'profitByBrands' => $report['profit_by_brands'],
            'profitByLocations' => $report['profit_by_locations'],
            'profitByInvoice' => $report['profit_by_invoice'],
            'profitByDate' => $report['profit_by_date'],
            'profitByCustomer' => $report['profit_by_customer'],
            'profitByDay' => $report['profit_by_day'],
            'profitByServiceStaff' => $report['profit_by_service_staff'],
        ]);
    }

    public function todayProfit(Team $current_team): JsonResponse
    {
        $today = Carbon::today();
        $report = $this->profitLossReportService->build($current_team, $today, $today, null);

        return response()->json([
            'date' => $today->toDateString(),
            'summary' => $report['summary'],
        ]);
    }
}
