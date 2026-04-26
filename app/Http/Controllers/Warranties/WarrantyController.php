<?php

namespace App\Http\Controllers\Warranties;

use App\Exports\WarrantiesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Warranties\StoreWarrantyRequest;
use App\Http\Requests\Warranties\UpdateWarrantyRequest;
use App\Http\Requests\Warranties\WarrantyIndexRequest;
use App\Http\Resources\WarrantyResource;
use App\Models\Team;
use App\Models\User;
use App\Models\Warranty;
use App\Services\WarrantyService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WarrantyController extends Controller
{
    public function __construct(
        protected WarrantyService $warrantyService,
    ) {}

    public function index(WarrantyIndexRequest $request, Team $current_team): Response
    {
        $this->authorizeWarrantyPermission($request->user(), $current_team, [
            'warranty.view',
            'warranty.view_own',
            'warranty.create',
            'warranty.update',
            'warranty.delete',
        ]);

        $filters = $request->filters();
        if (
            ! $request->user()?->hasPosPermission($current_team, 'warranty.view')
            && $request->user()?->hasPosPermission($current_team, 'warranty.view_own')
        ) {
            $filters['created_by'] = $request->user()?->id;
        }
        $paginator = $this->warrantyService->paginate($current_team, $filters);
        $paginator->through(fn (Warranty $w) => (new WarrantyResource($w))->resolve());

        $editing = null;
        if (
            $request->user()?->hasPosPermission($current_team, 'warranty.update')
            && ($editId = $request->query('edit'))
        ) {
            $editing = $current_team->warranties()->whereKey($editId)->first();
        }

        return Inertia::render('warranties/Index', [
            'warranties' => $paginator,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'sort' => $filters['sort'] ?? 'created_at',
                'direction' => $filters['direction'] ?? 'desc',
                'per_page' => $filters['per_page'] ?? 15,
                'duration_unit' => $filters['duration_unit'] ?? '',
            ],
            'editingWarranty' => $editing ? (new WarrantyResource($editing))->resolve() : null,
        ]);
    }

    public function store(StoreWarrantyRequest $request, Team $current_team): RedirectResponse
    {
        $this->authorizeWarrantyPermission($request->user(), $current_team, ['warranty.create']);
        $this->warrantyService->create($current_team, $request->validated(), $request->user()?->id);

        return to_route('warranties.index', ['current_team' => $current_team])
            ->with('success', 'Warranty created.');
    }

    public function update(UpdateWarrantyRequest $request, Team $current_team, Warranty $warranty): RedirectResponse
    {
        $this->authorizeWarrantyPermission($request->user(), $current_team, ['warranty.update']);
        $this->warrantyService->update($warranty, $request->validated());

        return to_route('warranties.index', ['current_team' => $current_team])
            ->with('success', 'Warranty updated.');
    }

    public function destroy(Request $request, Team $current_team, Warranty $warranty): RedirectResponse
    {
        $this->authorizeWarrantyPermission($request->user(), $current_team, ['warranty.delete']);
        $this->warrantyService->delete($warranty);

        return to_route('warranties.index', ['current_team' => $current_team])
            ->with('success', 'Warranty deleted.');
    }

    public function exportFile(WarrantyIndexRequest $request, Team $current_team, string $format): BinaryFileResponse|\Illuminate\Http\Response
    {
        $this->authorizeWarrantyPermission($request->user(), $current_team, ['warranty.view', 'warranty.view_own']);
        $format = strtolower($format);
        abort_unless(in_array($format, ['csv', 'xlsx', 'pdf'], true), 404);

        $filters = $request->filters();
        $export = new WarrantiesExport($current_team, $filters, $this->warrantyService);

        $filename = 'warranties-'.now()->format('Y-m-d-His');

        if ($format === 'csv') {
            return Excel::download($export, $filename.'.csv', \Maatwebsite\Excel\Excel::CSV);
        }

        if ($format === 'xlsx') {
            return Excel::download($export, $filename.'.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        }

        $rows = $this->warrantyService->filteredQuery($current_team, $filters)
            ->orderBy('created_at', 'desc')
            ->get();

        $pdf = Pdf::loadView('exports.warranties-pdf', [
            'team' => $current_team,
            'warranties' => $rows,
            'generatedAt' => now()->toDateTimeString(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download($filename.'.pdf');
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function authorizeWarrantyPermission(?User $user, Team $team, array $permissions): void
    {
        abort_unless($user?->hasAnyPosPermission($team, $permissions) ?? false, 403);
    }
}
