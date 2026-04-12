<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\TableReportRequest;
use App\Models\BusinessLocation;
use App\Models\Team;
use App\Services\TableReportService;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class TableReportController extends Controller
{
    public function __construct(
        protected TableReportService $tableReportService,
    ) {}

    public function tableReport(TableReportRequest $request, Team $current_team): Response
    {
        $validated = $request->validated();
        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);
        $locationId = isset($validated['business_location_id'])
            ? (int) $validated['business_location_id']
            : null;

        $report = $this->tableReportService->build($current_team, $start, $end, $locationId);

        $locations = BusinessLocation::query()
            ->forTeam($current_team)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('reports/TableReport', [
            'filters' => [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'business_location_id' => $locationId,
            ],
            'businessLocations' => $locations,
            'rows' => $report['rows'],
            'footer' => $report['footer'],
        ]);
    }
}
