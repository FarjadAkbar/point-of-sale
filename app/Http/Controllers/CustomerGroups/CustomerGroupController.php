<?php

namespace App\Http\Controllers\CustomerGroups;

use App\Exports\CustomerGroupsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerGroups\CustomerGroupIndexRequest;
use App\Http\Requests\CustomerGroups\StoreCustomerGroupRequest;
use App\Http\Requests\CustomerGroups\UpdateCustomerGroupRequest;
use App\Http\Resources\CustomerGroupResource;
use App\Models\CustomerGroup;
use App\Models\Team;
use App\Services\CustomerGroupService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CustomerGroupController extends Controller
{
    public function __construct(
        protected CustomerGroupService $customerGroupService,
    ) {}

    public function index(CustomerGroupIndexRequest $request, Team $current_team): Response
    {
        $filters = $request->filters();
        $paginator = $this->customerGroupService->paginate($current_team, $filters);
        $paginator->through(fn (CustomerGroup $g) => (new CustomerGroupResource($g))->resolve());

        $editing = null;
        if ($editId = $request->query('edit')) {
            $editing = $current_team->customerGroups()->whereKey($editId)->first();
            $editing?->load('sellingPriceGroup');
        }

        $sellingGroups = $current_team->sellingPriceGroups()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('customer-groups/Index', [
            'customerGroups' => $paginator,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'sort' => $filters['sort'] ?? 'created_at',
                'direction' => $filters['direction'] ?? 'desc',
                'per_page' => $filters['per_page'] ?? 15,
                'price_calculation_type' => $filters['price_calculation_type'] ?? '',
            ],
            'sellingPriceGroups' => $sellingGroups,
            'editingCustomerGroup' => $editing ? (new CustomerGroupResource($editing))->resolve() : null,
        ]);
    }

    public function store(StoreCustomerGroupRequest $request, Team $current_team): RedirectResponse
    {
        $this->customerGroupService->create($current_team, $request->validated());

        return to_route('customer-groups.index', ['current_team' => $current_team])
            ->with('success', 'Customer group created.');
    }

    public function update(UpdateCustomerGroupRequest $request, Team $current_team, CustomerGroup $customer_group): RedirectResponse
    {
        $this->customerGroupService->update($customer_group, $request->validated());

        return to_route('customer-groups.index', ['current_team' => $current_team])
            ->with('success', 'Customer group updated.');
    }

    public function destroy(Request $request, Team $current_team, CustomerGroup $customer_group): RedirectResponse
    {
        $this->customerGroupService->delete($customer_group);

        return to_route('customer-groups.index', ['current_team' => $current_team])
            ->with('success', 'Customer group deleted.');
    }

    public function exportFile(CustomerGroupIndexRequest $request, Team $current_team, string $format): BinaryFileResponse|\Illuminate\Http\Response
    {
        $format = strtolower($format);
        abort_unless(in_array($format, ['csv', 'xlsx', 'pdf'], true), 404);

        $filters = $request->filters();
        $export = new CustomerGroupsExport($current_team, $filters, $this->customerGroupService);

        $filename = 'customer-groups-'.now()->format('Y-m-d-His');

        if ($format === 'csv') {
            return Excel::download($export, $filename.'.csv', \Maatwebsite\Excel\Excel::CSV);
        }

        if ($format === 'xlsx') {
            return Excel::download($export, $filename.'.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        }

        $groups = $this->customerGroupService->filteredQuery($current_team, $filters)
            ->with('sellingPriceGroup')
            ->orderBy('created_at', 'desc')
            ->get();

        $pdf = Pdf::loadView('exports.customer-groups-pdf', [
            'team' => $current_team,
            'customerGroups' => $groups,
            'generatedAt' => now()->toDateTimeString(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download($filename.'.pdf');
    }
}
