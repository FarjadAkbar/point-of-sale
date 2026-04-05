<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Http\Requests\Products\ProductIndexRequest;
use App\Http\Requests\Products\StoreProductRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\VariationTemplateResource;
use App\Models\Brand;
use App\Models\BusinessLocation;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SellingPriceGroup;
use App\Models\Team;
use App\Models\Unit;
use App\Models\VariationTemplate;
use App\Services\ProductService;
use App\Services\UnitService;
use App\Support\ProductOptionLists;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService,
        protected UnitService $unitService,
    ) {}

    public function index(ProductIndexRequest $request, Team $current_team): Response
    {
        $filters = $request->filters();
        $paginator = $this->productService->paginate($current_team, $filters);
        $paginator->through(fn (Product $p) => (new ProductResource($p))->resolve());

        return Inertia::render('products/Index', [
            'products' => $paginator,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'sort' => $filters['sort'] ?? 'created_at',
                'direction' => $filters['direction'] ?? 'desc',
                'per_page' => $filters['per_page'] ?? 15,
                'product_type' => $filters['product_type'] ?? '',
            ],
        ]);
    }

    public function printLabels(Request $request, Team $current_team): Response
    {
        $sellingPriceGroups = SellingPriceGroup::query()
            ->forTeam($current_team)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('products/PrintLabels', [
            'sellingPriceGroups' => $sellingPriceGroups,
        ]);
    }

    public function create(Request $request, Team $current_team): Response
    {
        $units = Unit::query()->forTeam($current_team)->orderBy('name')->get([
            'id',
            'name',
            'short_name',
            'allow_decimal',
            'is_multiple_of_base',
            'base_unit_multiplier',
            'created_at',
        ]);
        $brands = Brand::query()->forTeam($current_team)->orderBy('name')->get([
            'id',
            'name',
            'description',
            'user_for_repair',
            'created_at',
        ]);
        $categories = ProductCategory::query()
            ->forTeam($current_team)
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id', 'is_sub_taxonomy']);

        $variationTemplates = VariationTemplate::query()
            ->forTeam($current_team)
            ->with('values')
            ->orderBy('name')
            ->get();

        $baseUnits = $this->unitService
            ->baseUnitOptionsQuery($current_team, null)
            ->get(['id', 'name', 'short_name']);

        return Inertia::render('products/Create', [
            'units' => $units,
            'brands' => $brands,
            'baseUnits' => $baseUnits,
            'categories' => $categories,
            'variationTemplates' => VariationTemplateResource::collection($variationTemplates)->resolve(),
            'barcodeTypes' => ProductOptionLists::barcodeTypes(),
            'taxOptions' => ProductOptionLists::taxOptions(),
            'businessLocations' => BusinessLocation::query()
                ->forTeam($current_team)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(StoreProductRequest $request, Team $current_team): RedirectResponse
    {
        $this->productService->create(
            $current_team,
            $request->productPayload(),
            $request->file('product_image'),
            $request->file('product_brochure'),
            $request->openingStocks(),
        );

        return to_route('products.index', ['current_team' => $current_team])
            ->with('success', 'Product created.');
    }

    public function search(Request $request, Team $current_team): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if (strlen($q) < 1) {
            return response()->json(['data' => []]);
        }

        $sellableOnly = filter_var($request->query('active_only', false), FILTER_VALIDATE_BOOLEAN);
        $locationRaw = $request->query('business_location_id');
        $businessLocationId = is_numeric($locationRaw) ? (int) $locationRaw : null;

        if ($businessLocationId !== null) {
            $exists = BusinessLocation::query()
                ->forTeam($current_team)
                ->whereKey($businessLocationId)
                ->exists();
            if (! $exists) {
                return response()->json(['data' => []]);
            }
        }

        $rows = $this->productService
            ->searchQuery($current_team, $q, $sellableOnly, $businessLocationId)
            ->get(['id', 'name', 'sku', 'product_type', 'single_dsp', 'combo_selling_price']);

        return response()->json([
            'data' => $rows->map(function (Product $p) {
                $defaultPrice = $p->product_type === 'combo'
                    ? ($p->combo_selling_price ?? null)
                    : ($p->single_dsp ?? null);

                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'sku' => $p->sku,
                    'text' => $p->name.($p->sku ? ' ('.$p->sku.')' : ''),
                    'default_unit_price' => $defaultPrice !== null ? (string) $defaultPrice : '0',
                ];
            }),
        ]);
    }

    public function importCsv(Request $request, Team $current_team): RedirectResponse
    {
        return back()->with('info', 'CSV import will be available in a future update.');
    }

    public function importXlsx(Request $request, Team $current_team): RedirectResponse
    {
        return back()->with('info', 'Excel import will be available in a future update.');
    }
}
