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
use App\Services\SupplierService;
use Barryvdh\DomPDF\Facade\Pdf;
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
        $filters = $request->filters();
        $paginator = $this->supplierService->paginate($current_team, $filters);
        $paginator->through(fn (Supplier $supplier) => (new SupplierResource($supplier))->resolve());

        $members = $current_team->members()
            ->orderBy('name')
            ->get(['users.id', 'users.name', 'users.email']);

        $editing = null;
        if ($editId = $request->query('edit')) {
            $editing = $current_team->suppliers()->whereKey($editId)->first();
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
        $supplier = $this->supplierService->create($current_team, $request->validated());

        return to_route('suppliers.index', ['current_team' => $current_team])
            ->with('success', 'Supplier created.');
    }

    public function update(UpdateSupplierRequest $request, Team $current_team, Supplier $supplier): RedirectResponse
    {
        $this->supplierService->update($supplier, $request->validated());

        return to_route('suppliers.index', ['current_team' => $current_team])
            ->with('success', 'Supplier updated.');
    }

    public function destroy(Request $request, Team $current_team, Supplier $supplier): RedirectResponse
    {
        $this->supplierService->delete($supplier);

        return to_route('suppliers.index', ['current_team' => $current_team])
            ->with('success', 'Supplier deleted.');
    }

    public function exportFile(SupplierIndexRequest $request, Team $current_team, string $format): BinaryFileResponse|\Illuminate\Http\Response
    {
        $format = strtolower($format);
        abort_unless(in_array($format, ['csv', 'xlsx', 'pdf'], true), 404);

        $filters = $request->filters();
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
}
