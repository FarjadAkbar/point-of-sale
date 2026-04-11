<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\CustomerGroupReportRequest;
use App\Models\BusinessLocation;
use App\Models\CustomerGroup;
use App\Models\Team;
use App\Services\CustomerGroupReportService;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class CustomerGroupReportController extends Controller
{
    public function __construct(
        protected CustomerGroupReportService $customerGroupReportService,
    ) {}

    public function customerGroupReport(CustomerGroupReportRequest $request, Team $current_team): Response
    {
        $validated = $request->validated();
        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);
        $locationId = isset($validated['business_location_id'])
            ? (int) $validated['business_location_id']
            : null;
        $groupId = isset($validated['customer_group_id'])
            ? (int) $validated['customer_group_id']
            : null;

        $rows = $this->customerGroupReportService->build(
            $current_team,
            $start,
            $end,
            $locationId,
            $groupId,
        );

        $locations = BusinessLocation::query()
            ->forTeam($current_team)
            ->orderBy('name')
            ->get(['id', 'name']);

        $customerGroups = CustomerGroup::query()
            ->forTeam($current_team)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('reports/CustomerGroupReport', [
            'filters' => [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'business_location_id' => $locationId,
                'customer_group_id' => $groupId,
            ],
            'businessLocations' => $locations,
            'customerGroups' => $customerGroups,
            'rows' => $rows,
        ]);
    }
}
