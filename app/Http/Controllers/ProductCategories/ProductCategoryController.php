<?php

namespace App\Http\Controllers\ProductCategories;

use App\Exports\ProductCategoriesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductCategories\ProductCategoryIndexRequest;
use App\Http\Requests\ProductCategories\StoreProductCategoryRequest;
use App\Http\Requests\ProductCategories\UpdateProductCategoryRequest;
use App\Http\Resources\ProductCategoryResource;
use App\Models\ProductCategory;
use App\Models\Team;
use App\Services\ProductCategoryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductCategoryController extends Controller
{
    public function __construct(
        protected ProductCategoryService $productCategoryService,
    ) {}

    public function index(ProductCategoryIndexRequest $request, Team $current_team): Response
    {
        $filters = $request->filters();
        $paginator = $this->productCategoryService->paginate($current_team, $filters);
        $paginator->through(fn (ProductCategory $c) => (new ProductCategoryResource($c))->resolve());

        $editing = null;
        if ($editId = $request->query('edit')) {
            $editing = $current_team->productCategories()->whereKey($editId)->first();
            $editing?->load('parent');
        }

        $parents = $this->productCategoryService
            ->parentOptionsQuery($current_team, $editing?->id)
            ->get(['id', 'name']);

        return Inertia::render('product-categories/Index', [
            'productCategories' => $paginator,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'sort' => $filters['sort'] ?? 'created_at',
                'direction' => $filters['direction'] ?? 'desc',
                'per_page' => $filters['per_page'] ?? 15,
            ],
            'parentCategories' => $parents,
            'editingProductCategory' => $editing ? (new ProductCategoryResource($editing))->resolve() : null,
        ]);
    }

    public function store(StoreProductCategoryRequest $request, Team $current_team): RedirectResponse
    {
        $this->productCategoryService->create($current_team, $request->validated());

        return to_route('product-categories.index', ['current_team' => $current_team])
            ->with('success', 'Category created.');
    }

    public function update(UpdateProductCategoryRequest $request, Team $current_team, ProductCategory $product_category): RedirectResponse
    {
        $this->productCategoryService->update($product_category, $request->validated());

        return to_route('product-categories.index', ['current_team' => $current_team])
            ->with('success', 'Category updated.');
    }

    public function destroy(Request $request, Team $current_team, ProductCategory $product_category): RedirectResponse
    {
        $this->productCategoryService->delete($product_category);

        return to_route('product-categories.index', ['current_team' => $current_team])
            ->with('success', 'Category deleted.');
    }

    public function exportFile(ProductCategoryIndexRequest $request, Team $current_team, string $format): BinaryFileResponse|\Illuminate\Http\Response
    {
        $format = strtolower($format);
        abort_unless(in_array($format, ['csv', 'xlsx', 'pdf'], true), 404);

        $filters = $request->filters();
        $export = new ProductCategoriesExport($current_team, $filters, $this->productCategoryService);

        $filename = 'product-categories-'.now()->format('Y-m-d-His');

        if ($format === 'csv') {
            return Excel::download($export, $filename.'.csv', \Maatwebsite\Excel\Excel::CSV);
        }

        if ($format === 'xlsx') {
            return Excel::download($export, $filename.'.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        }

        $rows = $this->productCategoryService->filteredQuery($current_team, $filters)
            ->with('parent')
            ->orderBy('created_at', 'desc')
            ->get();

        $pdf = Pdf::loadView('exports.product-categories-pdf', [
            'team' => $current_team,
            'productCategories' => $rows,
            'generatedAt' => now()->toDateTimeString(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download($filename.'.pdf');
    }
}
