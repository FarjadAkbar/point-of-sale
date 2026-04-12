<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\ExpenseReportRequest;
use App\Models\BusinessLocation;
use App\Models\ExpenseCategory;
use App\Models\Team;
use App\Services\ExpenseReportService;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseReportController extends Controller
{
    public function __construct(
        protected ExpenseReportService $expenseReportService,
    ) {}

    public function expenseReport(ExpenseReportRequest $request, Team $current_team): Response
    {
        $validated = $request->validated();
        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);
        $locationId = isset($validated['business_location_id'])
            ? (int) $validated['business_location_id']
            : null;
        $categoryId = isset($validated['category_id'])
            ? (int) $validated['category_id']
            : null;

        $report = $this->expenseReportService->build($current_team, $start, $end, $locationId, $categoryId);

        $locations = BusinessLocation::query()
            ->forTeam($current_team)
            ->orderBy('name')
            ->get(['id', 'name']);

        $categories = ExpenseCategory::query()
            ->forTeam($current_team)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('reports/ExpenseReport', [
            'filters' => [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'business_location_id' => $locationId,
                'category_id' => $categoryId,
            ],
            'businessLocations' => $locations,
            'categories' => $categories,
            'chart' => $report['chart'],
            'rows' => $report['rows'],
            'footer' => $report['footer'],
        ]);
    }
}
