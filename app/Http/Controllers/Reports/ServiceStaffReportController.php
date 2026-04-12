<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\ServiceStaffReportRequest;
use App\Models\BusinessLocation;
use App\Models\Team;
use App\Services\ServiceStaffReportService;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ServiceStaffReportController extends Controller
{
    public function __construct(
        protected ServiceStaffReportService $serviceStaffReportService,
    ) {}

    public function serviceStaffReport(ServiceStaffReportRequest $request, Team $current_team): Response
    {
        $validated = $request->validated();
        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);
        $locationId = isset($validated['business_location_id'])
            ? (int) $validated['business_location_id']
            : null;
        $staffId = isset($validated['service_staff_id'])
            ? (int) $validated['service_staff_id']
            : null;

        $report = $this->serviceStaffReportService->build(
            $current_team,
            $start,
            $end,
            $locationId,
            $staffId,
        );

        $locations = BusinessLocation::query()
            ->forTeam($current_team)
            ->orderBy('name')
            ->get(['id', 'name']);

        $users = $current_team->members()
            ->orderBy('users.name')
            ->get(['users.id', 'users.name'])
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])
            ->values()
            ->all();

        return Inertia::render('reports/ServiceStaffReport', [
            'filters' => [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'business_location_id' => $locationId,
                'service_staff_id' => $staffId,
            ],
            'businessLocations' => $locations,
            'serviceStaff' => $users,
            'orderRows' => $report['order_rows'],
            'orderFooter' => $report['order_footer'],
            'lineRows' => $report['line_rows'],
            'lineFooter' => $report['line_footer'],
        ]);
    }
}
