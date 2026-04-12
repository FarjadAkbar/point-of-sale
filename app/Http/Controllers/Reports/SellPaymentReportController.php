<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\SellPaymentReportRequest;
use App\Models\BusinessLocation;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Team;
use App\Services\SellPaymentReportService;
use App\Support\PaymentMethodLabels;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class SellPaymentReportController extends Controller
{
    public function __construct(
        protected SellPaymentReportService $sellPaymentReportService,
    ) {}

    public function sellPayments(SellPaymentReportRequest $request, Team $current_team): Response
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
        $customerGroupId = isset($validated['customer_group_id'])
            ? (int) $validated['customer_group_id']
            : null;
        $paymentMethod = isset($validated['payment_method'])
            ? (string) $validated['payment_method']
            : null;

        $report = $this->sellPaymentReportService->build(
            $current_team,
            $start,
            $end,
            $locationId,
            $customerId,
            $customerGroupId,
            $paymentMethod,
        );

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

        $customerGroups = CustomerGroup::query()
            ->forTeam($current_team)
            ->orderBy('name')
            ->get(['id', 'name']);

        $paymentMethodOptions = collect(PaymentMethodLabels::options())
            ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
            ->values()
            ->all();

        return Inertia::render('reports/SellPaymentReport', [
            'filters' => [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'business_location_id' => $locationId,
                'customer_id' => $customerId,
                'customer_group_id' => $customerGroupId,
                'payment_method' => $paymentMethod,
            ],
            'businessLocations' => $locations,
            'customers' => $customers,
            'customerGroups' => $customerGroups,
            'paymentMethodOptions' => $paymentMethodOptions,
            'rows' => $report['rows'],
            'footer' => $report['footer'],
        ]);
    }
}
