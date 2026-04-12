<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\SalesRepresentativeReportRequest;
use App\Models\BusinessLocation;
use App\Models\Team;
use App\Services\SalesRepresentativeReportService;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class SalesRepresentativeReportController extends Controller
{
    public function __construct(
        protected SalesRepresentativeReportService $salesRepresentativeReportService,
    ) {}

    public function salesRepresentativeReport(
        SalesRepresentativeReportRequest $request,
        Team $current_team,
    ): Response {
        $validated = $request->validated();
        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);
        $locationId = isset($validated['business_location_id'])
            ? (int) $validated['business_location_id']
            : null;
        $userId = isset($validated['user_id'])
            ? (int) $validated['user_id']
            : null;

        $report = $this->salesRepresentativeReportService->build(
            $current_team,
            $start,
            $end,
            $locationId,
            $userId,
        );

        $users = $current_team->members()
            ->orderBy('users.name')
            ->get(['users.id', 'users.name'])
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])
            ->values()
            ->all();

        $locations = BusinessLocation::query()
            ->forTeam($current_team)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('reports/SalesRepresentativeReport', [
            'filters' => [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'business_location_id' => $locationId,
                'user_id' => $userId,
            ],
            'users' => $users,
            'businessLocations' => $locations,
            'summary' => $report['summary'],
            'salesRows' => $report['sales_rows'],
            'salesFooter' => $report['sales_footer'],
            'commissionRows' => $report['commission_rows'],
            'commissionFooter' => $report['commission_footer'],
            'expenseRows' => $report['expense_rows'],
            'expenseFooter' => $report['expense_footer'],
        ]);
    }
}
