<?php

namespace App\Http\Controllers\Brands;

use App\Exports\BrandsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Brands\BrandIndexRequest;
use App\Http\Requests\Brands\StoreBrandRequest;
use App\Http\Requests\Brands\UpdateBrandRequest;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use App\Models\Team;
use App\Services\BrandService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BrandController extends Controller
{
    public function __construct(
        protected BrandService $brandService,
    ) {}

    public function index(BrandIndexRequest $request, Team $current_team): Response
    {
        $filters = $request->filters();
        $paginator = $this->brandService->paginate($current_team, $filters);
        $paginator->through(fn (Brand $b) => (new BrandResource($b))->resolve());

        $editing = null;
        if ($editId = $request->query('edit')) {
            $editing = $current_team->brands()->whereKey($editId)->first();
        }

        return Inertia::render('brands/Index', [
            'brands' => $paginator,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'sort' => $filters['sort'] ?? 'created_at',
                'direction' => $filters['direction'] ?? 'desc',
                'per_page' => $filters['per_page'] ?? 15,
            ],
            'editingBrand' => $editing ? (new BrandResource($editing))->resolve() : null,
        ]);
    }

    public function store(StoreBrandRequest $request, Team $current_team): RedirectResponse
    {
        $this->brandService->create($current_team, $request->validated());

        return to_route('brands.index', ['current_team' => $current_team])
            ->with('success', 'Brand created.');
    }

    public function quickStore(StoreBrandRequest $request, Team $current_team): JsonResponse
    {
        $brand = $this->brandService->create($current_team, $request->validated());

        return response()->json((new BrandResource($brand))->resolve(), 201);
    }

    public function update(UpdateBrandRequest $request, Team $current_team, Brand $brand): RedirectResponse
    {
        $this->brandService->update($brand, $request->validated());

        return to_route('brands.index', ['current_team' => $current_team])
            ->with('success', 'Brand updated.');
    }

    public function destroy(Request $request, Team $current_team, Brand $brand): RedirectResponse
    {
        $this->brandService->delete($brand);

        return to_route('brands.index', ['current_team' => $current_team])
            ->with('success', 'Brand deleted.');
    }

    public function exportFile(BrandIndexRequest $request, Team $current_team, string $format): BinaryFileResponse|\Illuminate\Http\Response
    {
        $format = strtolower($format);
        abort_unless(in_array($format, ['csv', 'xlsx', 'pdf'], true), 404);

        $filters = $request->filters();
        $export = new BrandsExport($current_team, $filters, $this->brandService);

        $filename = 'brands-'.now()->format('Y-m-d-His');

        if ($format === 'csv') {
            return Excel::download($export, $filename.'.csv', \Maatwebsite\Excel\Excel::CSV);
        }

        if ($format === 'xlsx') {
            return Excel::download($export, $filename.'.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        }

        $rows = $this->brandService->filteredQuery($current_team, $filters)
            ->orderBy('created_at', 'desc')
            ->get();

        $pdf = Pdf::loadView('exports.brands-pdf', [
            'team' => $current_team,
            'brands' => $rows,
            'generatedAt' => now()->toDateTimeString(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download($filename.'.pdf');
    }
}
