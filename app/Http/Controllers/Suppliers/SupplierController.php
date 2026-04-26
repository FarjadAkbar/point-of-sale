<?php

namespace App\Http\Controllers\Suppliers;

use App\Exports\SuppliersExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Suppliers\StoreSupplierRequest;
use App\Http\Requests\Suppliers\SupplierIndexRequest;
use App\Http\Requests\Suppliers\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use App\Models\Team;
use App\Models\User;
use App\Services\SupplierService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SupplierController extends Controller
{
    public function __construct(
        protected SupplierService $supplierService,
    ) {}

    public function index(SupplierIndexRequest $request, Team $current_team): Response
    {
        $filters = array_merge(
            $request->filters(),
            $this->supplierPermissionFilters($request->user(), $current_team),
        );
        $paginator = $this->supplierService->paginate($current_team, $filters);
        $paginator->through(fn (Supplier $supplier) => (new SupplierResource($supplier))->resolve());

        $members = $current_team->members()
            ->orderBy('name')
            ->get(['users.id', 'users.name', 'users.email']);

        $editing = null;
        if ($editId = $request->query('edit')) {
            $candidate = $current_team->suppliers()->whereKey($editId)->first();
            if ($candidate && $this->canManageSupplier($request->user(), $current_team, $candidate)) {
                $editing = $candidate;
            }
            $editing?->load(['assignedUsers', 'contactPersons']);
        }

        return Inertia::render('suppliers/Index', [
            'suppliers' => $paginator,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'sort' => $filters['sort'] ?? 'created_at',
                'direction' => $filters['direction'] ?? 'desc',
                'per_page' => $filters['per_page'] ?? 15,
                'contact_type' => $filters['contact_type'] ?? '',
            ],
            'teamMembers' => $members->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
            ]),
            'editingSupplier' => $editing ? (new SupplierResource($editing))->resolve() : null,
        ]);
    }

    public function store(StoreSupplierRequest $request, Team $current_team): RedirectResponse
    {
        $data = $request->validated();
        if (
            ! $request->user()?->hasPosPermission($current_team, 'supplier.view')
            && $request->user()?->hasPosPermission($current_team, 'supplier.view_own')
        ) {
            $data['assigned_to_users'] = array_values(array_unique(array_merge(
                (array) ($data['assigned_to_users'] ?? []),
                [$request->user()?->id],
            )));
        }

        $supplier = $this->supplierService->create($current_team, $data);

        return to_route('suppliers.index', ['current_team' => $current_team])
            ->with('success', 'Supplier created.');
    }

    public function quickStore(StoreSupplierRequest $request, Team $current_team): JsonResponse
    {
        $data = $request->validated();
        if (
            ! $request->user()?->hasPosPermission($current_team, 'supplier.view')
            && $request->user()?->hasPosPermission($current_team, 'supplier.view_own')
        ) {
            $data['assigned_to_users'] = array_values(array_unique(array_merge(
                (array) ($data['assigned_to_users'] ?? []),
                [$request->user()?->id],
            )));
        }

        $supplier = $this->supplierService->create($current_team, $data);

        return response()->json([
            'supplier' => [
                'id' => $supplier->id,
                'display_name' => $supplier->display_name,
                'address_line_1' => $supplier->address_line_1,
                'address_line_2' => $supplier->address_line_2,
                'city' => $supplier->city,
                'state' => $supplier->state,
                'zip_code' => $supplier->zip_code,
                'country' => $supplier->country,
            ],
        ], 201);
    }

    public function update(UpdateSupplierRequest $request, Team $current_team, Supplier $supplier): RedirectResponse
    {
        abort_unless($this->canManageSupplier($request->user(), $current_team, $supplier), 403);
        $this->supplierService->update($supplier, $request->validated());

        return to_route('suppliers.index', ['current_team' => $current_team])
            ->with('success', 'Supplier updated.');
    }

    public function destroy(Request $request, Team $current_team, Supplier $supplier): RedirectResponse
    {
        abort_unless($this->canManageSupplier($request->user(), $current_team, $supplier), 403);
        $this->supplierService->delete($supplier);

        return to_route('suppliers.index', ['current_team' => $current_team])
            ->with('success', 'Supplier deleted.');
    }

    public function exportFile(SupplierIndexRequest $request, Team $current_team, string $format): BinaryFileResponse|\Illuminate\Http\Response
    {
        $format = strtolower($format);
        abort_unless(in_array($format, ['csv', 'xlsx', 'pdf'], true), 404);

        $filters = array_merge(
            $request->filters(),
            $this->supplierPermissionFilters($request->user(), $current_team),
        );
        $export = new SuppliersExport($current_team, $filters, $this->supplierService);

        $filename = 'suppliers-'.now()->format('Y-m-d-His');

        if ($format === 'csv') {
            return Excel::download($export, $filename.'.csv', \Maatwebsite\Excel\Excel::CSV);
        }

        if ($format === 'xlsx') {
            return Excel::download($export, $filename.'.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        }

        $suppliers = $this->supplierService->filteredQuery($current_team, $filters)
            ->orderBy('created_at', 'desc')
            ->get();

        $pdf = Pdf::loadView('exports.suppliers-pdf', [
            'team' => $current_team,
            'suppliers' => $suppliers,
            'generatedAt' => now()->toDateTimeString(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download($filename.'.pdf');
    }

    /**
     * @return array<string, mixed>
     */
    private function supplierPermissionFilters(?User $user, Team $team): array
    {
        if (! $user || $user->ownsTeam($team) || $user->hasPosPermission($team, 'supplier.view')) {
            return [];
        }

        if ($user->hasPosPermission($team, 'supplier.view_own')) {
            return ['assigned_user_id' => $user->id];
        }

        return [];
    }

    private function canManageSupplier(?User $user, Team $team, Supplier $supplier): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->ownsTeam($team) || $user->hasPosPermission($team, 'supplier.view')) {
            return true;
        }

        if ($user->hasPosPermission($team, 'supplier.view_own')) {
            return $supplier->assignedUsers()
                ->where('users.id', $user->id)
                ->exists();
        }

        return false;
    }
}
