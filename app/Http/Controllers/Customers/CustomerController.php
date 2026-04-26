<?php

namespace App\Http\Controllers\Customers;

use App\Exports\CustomersExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customers\CustomerIndexRequest;
use App\Http\Requests\Customers\StoreCustomerRequest;
use App\Http\Requests\Customers\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\Team;
use App\Models\User;
use App\Services\CustomerService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CustomerController extends Controller
{
    public function __construct(
        protected CustomerService $customerService,
    ) {}

    public function index(CustomerIndexRequest $request, Team $current_team): Response
    {
        $filters = $request->filters();
        $permissionFilters = $this->customerPermissionFilters($request->user(), $current_team);
        $filters = array_merge($filters, $permissionFilters);
        $paginator = $this->customerService->paginate($current_team, $filters);
        $paginator->through(fn (Customer $customer) => (new CustomerResource($customer))->resolve());

        $members = $current_team->members()
            ->orderBy('name')
            ->get(['users.id', 'users.name', 'users.email']);

        $groups = $current_team->customerGroups()->orderBy('name')->get(['id', 'name']);

        $editing = null;
        if ($editId = $request->query('edit')) {
            $candidate = $current_team->customers()->whereKey($editId)->first();
            if ($candidate && $this->canManageCustomer($request->user(), $current_team, $candidate)) {
                $editing = $candidate;
            }
            $editing?->load(['assignedUsers', 'contactPersons', 'customerGroup']);
        }

        return Inertia::render('customers/Index', [
            'customers' => $paginator,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'sort' => $filters['sort'] ?? 'created_at',
                'direction' => $filters['direction'] ?? 'desc',
                'per_page' => $filters['per_page'] ?? 15,
                'party_role' => $filters['party_role'] ?? '',
                'entity_type' => $filters['entity_type'] ?? '',
            ],
            'teamMembers' => $members->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
            ]),
            'customerGroups' => $groups,
            'editingCustomer' => $editing ? (new CustomerResource($editing))->resolve() : null,
        ]);
    }

    public function store(StoreCustomerRequest $request, Team $current_team): RedirectResponse
    {
        $data = $request->validated();
        if (
            ! $request->user()?->hasPosPermission($current_team, 'customer.view')
            && $request->user()?->hasPosPermission($current_team, 'customer.view_own')
        ) {
            $data['assigned_to_users'] = array_values(array_unique(array_merge(
                (array) ($data['assigned_to_users'] ?? []),
                [$request->user()?->id],
            )));
        }

        $this->customerService->create($current_team, $data);

        return to_route('customers.index', ['current_team' => $current_team])
            ->with('success', 'Customer created.');
    }

    public function quickStore(StoreCustomerRequest $request, Team $current_team): JsonResponse
    {
        $data = $request->validated();
        if (
            ! $request->user()?->hasPosPermission($current_team, 'customer.view')
            && $request->user()?->hasPosPermission($current_team, 'customer.view_own')
        ) {
            $data['assigned_to_users'] = array_values(array_unique(array_merge(
                (array) ($data['assigned_to_users'] ?? []),
                [$request->user()?->id],
            )));
        }

        $customer = $this->customerService->create($current_team, $data);

        return response()->json([
            'customer' => [
                'id' => $customer->id,
                'display_name' => $customer->display_name,
                'address_line_1' => $customer->address_line_1,
                'address_line_2' => $customer->address_line_2,
                'city' => $customer->city,
                'state' => $customer->state,
                'zip_code' => $customer->zip_code,
                'country' => $customer->country,
                'shipping_address' => $customer->shipping_address,
            ],
        ], 201);
    }

    public function update(UpdateCustomerRequest $request, Team $current_team, Customer $customer): RedirectResponse
    {
        abort_unless($this->canManageCustomer($request->user(), $current_team, $customer), 403);
        $this->customerService->update($customer, $request->validated());

        return to_route('customers.index', ['current_team' => $current_team])
            ->with('success', 'Customer updated.');
    }

    public function destroy(Request $request, Team $current_team, Customer $customer): RedirectResponse
    {
        abort_unless($this->canManageCustomer($request->user(), $current_team, $customer), 403);
        $this->customerService->delete($customer);

        return to_route('customers.index', ['current_team' => $current_team])
            ->with('success', 'Customer deleted.');
    }

    public function exportFile(CustomerIndexRequest $request, Team $current_team, string $format): BinaryFileResponse|\Illuminate\Http\Response
    {
        $format = strtolower($format);
        abort_unless(in_array($format, ['csv', 'xlsx', 'pdf'], true), 404);

        $filters = array_merge(
            $request->filters(),
            $this->customerPermissionFilters($request->user(), $current_team),
        );
        $export = new CustomersExport($current_team, $filters, $this->customerService);

        $filename = 'customers-'.now()->format('Y-m-d-His');

        if ($format === 'csv') {
            return Excel::download($export, $filename.'.csv', \Maatwebsite\Excel\Excel::CSV);
        }

        if ($format === 'xlsx') {
            return Excel::download($export, $filename.'.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        }

        $customers = $this->customerService->filteredQuery($current_team, $filters)
            ->orderBy('created_at', 'desc')
            ->get();

        $pdf = Pdf::loadView('exports.customers-pdf', [
            'team' => $current_team,
            'customers' => $customers,
            'generatedAt' => now()->toDateTimeString(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download($filename.'.pdf');
    }

    /**
     * @return array<string, mixed>
     */
    private function customerPermissionFilters(?User $user, Team $team): array
    {
        if (! $user || $user->ownsTeam($team)) {
            return [];
        }

        $filters = [];

        if (! $user->hasPosPermission($team, 'customer.view') && $user->hasPosPermission($team, 'customer.view_own')) {
            $filters['assigned_user_id'] = $user->id;
        }

        if ($user->hasPosPermission($team, 'customer_irrespective_of_sell')) {
            return $filters;
        }

        $noSellWindows = [
            'customer_with_no_sell_one_month' => now()->subMonth()->startOfDay(),
            'customer_with_no_sell_three_month' => now()->subMonths(3)->startOfDay(),
            'customer_with_no_sell_six_month' => now()->subMonths(6)->startOfDay(),
            'customer_with_no_sell_one_year' => now()->subYear()->startOfDay(),
        ];

        foreach ($noSellWindows as $permission => $date) {
            if ($user->hasPosPermission($team, $permission)) {
                $filters['no_sell_since'] = $date;
                break;
            }
        }

        return $filters;
    }

    private function canManageCustomer(?User $user, Team $team, Customer $customer): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->ownsTeam($team) || $user->hasPosPermission($team, 'customer.view')) {
            return true;
        }

        if ($user->hasPosPermission($team, 'customer.view_own')) {
            return $customer->assignedUsers()
                ->where('users.id', $user->id)
                ->exists();
        }

        return false;
    }
}
