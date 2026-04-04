<?php

namespace App\Http\Controllers\Units;

use App\Exports\UnitsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Units\StoreUnitRequest;
use App\Http\Requests\Units\UnitIndexRequest;
use App\Http\Requests\Units\UpdateUnitRequest;
use App\Http\Resources\UnitResource;
use App\Models\Team;
use App\Models\Unit;
use App\Services\UnitService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UnitController extends Controller
{
    public function __construct(
        protected UnitService $unitService,
    ) {}

    public function index(UnitIndexRequest $request, Team $current_team): Response
    {
        $filters = $request->filters();
        $paginator = $this->unitService->paginate($current_team, $filters);
        $paginator->through(fn (Unit $u) => (new UnitResource($u))->resolve());

        $editing = null;
        if ($editId = $request->query('edit')) {
            $editing = $current_team->units()->whereKey($editId)->first();
            $editing?->load('baseUnit');
        }

        $baseUnits = $this->unitService
            ->baseUnitOptionsQuery($current_team, $editing?->id)
            ->get(['id', 'name', 'short_name']);

        return Inertia::render('units/Index', [
            'units' => $paginator,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'sort' => $filters['sort'] ?? 'created_at',
                'direction' => $filters['direction'] ?? 'desc',
                'per_page' => $filters['per_page'] ?? 15,
            ],
            'baseUnits' => $baseUnits,
            'editingUnit' => $editing ? (new UnitResource($editing))->resolve() : null,
        ]);
    }

    public function store(StoreUnitRequest $request, Team $current_team): RedirectResponse
    {
        $this->unitService->create($current_team, $request->validated());

        return to_route('units.index', ['current_team' => $current_team])
            ->with('success', 'Unit created.');
    }

    public function quickStore(StoreUnitRequest $request, Team $current_team): JsonResponse
    {
        $unit = $this->unitService->create($current_team, $request->validated());

        return response()->json((new UnitResource($unit))->resolve(), 201);
    }

    public function update(UpdateUnitRequest $request, Team $current_team, Unit $unit): RedirectResponse
    {
        $this->unitService->update($unit, $request->validated());

        return to_route('units.index', ['current_team' => $current_team])
            ->with('success', 'Unit updated.');
    }

    public function destroy(Request $request, Team $current_team, Unit $unit): RedirectResponse
    {
        $this->unitService->delete($unit);

        return to_route('units.index', ['current_team' => $current_team])
            ->with('success', 'Unit deleted.');
    }

    public function exportFile(UnitIndexRequest $request, Team $current_team, string $format): BinaryFileResponse|\Illuminate\Http\Response
    {
        $format = strtolower($format);
        abort_unless(in_array($format, ['csv', 'xlsx', 'pdf'], true), 404);

        $filters = $request->filters();
        $export = new UnitsExport($current_team, $filters, $this->unitService);

        $filename = 'units-'.now()->format('Y-m-d-His');

        if ($format === 'csv') {
            return Excel::download($export, $filename.'.csv', \Maatwebsite\Excel\Excel::CSV);
        }

        if ($format === 'xlsx') {
            return Excel::download($export, $filename.'.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        }

        $rows = $this->unitService->filteredQuery($current_team, $filters)
            ->with('baseUnit')
            ->orderBy('created_at', 'desc')
            ->get();

        $pdf = Pdf::loadView('exports.units-pdf', [
            'team' => $current_team,
            'units' => $rows,
            'generatedAt' => now()->toDateTimeString(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download($filename.'.pdf');
    }
}
