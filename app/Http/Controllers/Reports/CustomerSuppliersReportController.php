<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\CustomerSuppliersReportRequest;
use App\Models\BusinessLocation;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Supplier;
use App\Models\Team;
use App\Services\CustomerSuppliersReportService;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class CustomerSuppliersReportController extends Controller
{
    public function __construct(
        protected CustomerSuppliersReportService $customerSuppliersReportService,
    ) {}

    public function customerSuppliersReport(CustomerSuppliersReportRequest $request, Team $current_team): Response
    {
        $validated = $request->validated();
        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);
        $businessLocationId = isset($validated['business_location_id'])
            ? (int) $validated['business_location_id']
            : null;
        $customerGroupId = isset($validated['customer_group_id'])
            ? (int) $validated['customer_group_id']
            : null;
        $contactType = (string) ($validated['contact_type'] ?? '');
        $contactKey = isset($validated['contact_key']) && $validated['contact_key'] !== ''
            ? (string) $validated['contact_key']
            : null;

        $report = $this->customerSuppliersReportService->build(
            $current_team,
            $start,
            $end,
            $businessLocationId,
            $contactType,
            $customerGroupId,
            $contactKey,
        );

        $locations = BusinessLocation::query()
            ->forTeam($current_team)
            ->orderBy('name')
            ->get(['id', 'name']);

        $customerGroups = CustomerGroup::query()
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
                'value' => 'c-'.$c->id,
                'label' => trim($c->display_name.(filled($c->customer_code) ? ' ('.$c->customer_code.')' : '')),
            ]);

        $suppliers = Supplier::query()
            ->forTeam($current_team)
            ->orderBy('business_name')
            ->orderBy('first_name')
            ->get(['id', 'supplier_code', 'business_name', 'first_name', 'last_name', 'entity_type'])
            ->map(function (Supplier $s) {
                $parts = array_filter([
                    trim(implode(' ', array_filter([$s->first_name, $s->last_name]))),
                    $s->business_name,
                ]);
                $name = $parts !== [] ? implode(', ', $parts) : $s->display_name;
                $label = filled($s->supplier_code) ? $name.' ('.$s->supplier_code.')' : $name;

                return [
                    'value' => 's-'.$s->id,
                    'label' => $label,
                ];
            });

        $contacts = $customers->concat($suppliers)->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)->values();

        return Inertia::render('reports/CustomerSuppliersReport', [
            'filters' => [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'business_location_id' => $businessLocationId,
                'customer_group_id' => $customerGroupId,
                'contact_type' => in_array($contactType, ['', 'all'], true) ? '' : $contactType,
                'contact_key' => $contactKey,
            ],
            'businessLocations' => $locations,
            'customerGroups' => $customerGroups,
            'contacts' => $contacts,
            'rows' => $report['rows'],
            'footer' => $report['footer'],
        ]);
    }
}
