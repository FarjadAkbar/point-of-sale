<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Http\Requests\Products\ProductIndexRequest;
use App\Http\Requests\Products\StoreProductRequest;
use App\Http\Requests\Products\UpdateProductRequest;
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
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        return Inertia::render('products/Create', $this->productFormPageProps($current_team));
    }

    public function edit(Request $request, Team $current_team, Product $product): Response
    {
        $product->load(['stocks', 'category', 'subcategory']);

        return Inertia::render('products/Create', [
            ...$this->productFormPageProps($current_team),
            'product' => $this->productForForm($product),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function productFormPageProps(Team $current_team): array
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

        return [
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
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function productForForm(Product $product): array
    {
        $decimal = fn ($v) => $v !== null ? (string) $v : '';

        $comboLines = $product->combo_lines;
        if (is_array($comboLines)) {
            $comboLines = array_map(function ($row) {
                if (! is_array($row)) {
                    return $row;
                }

                return [
                    'product_id' => (int) ($row['product_id'] ?? 0),
                    'product_name' => (string) ($row['product_name'] ?? ''),
                    'quantity' => isset($row['quantity']) ? (string) $row['quantity'] : '1',
                    'purchase_price_exc_tax' => isset($row['purchase_price_exc_tax'])
                        ? (string) $row['purchase_price_exc_tax']
                        : '0',
                    'line_total_exc_tax' => isset($row['line_total_exc_tax'])
                        ? (string) $row['line_total_exc_tax']
                        : '0',
                ];
            }, $comboLines);
        }

        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'barcode_type' => $product->barcode_type ?? 'none',
            'unit_id' => $product->unit_id,
            'brand_id' => $product->brand_id,
            'category_id' => $product->category_id,
            'subcategory_id' => $product->subcategory_id,
            'business_location_ids' => $product->business_location_ids ?? [],
            'location_stocks' => $product->stocks->map(fn ($s) => [
                'business_location_id' => (int) $s->business_location_id,
                'quantity' => (string) $s->quantity,
            ])->values()->all(),
            'manage_stock' => (bool) $product->manage_stock,
            'alert_quantity' => $decimal($product->alert_quantity),
            'description' => (string) ($product->description ?? ''),
            'enable_imei_serial' => (bool) $product->enable_imei_serial,
            'not_for_selling' => (bool) $product->not_for_selling,
            'weight' => $decimal($product->weight),
            'preparation_time_minutes' => $product->preparation_time_minutes !== null
                ? (string) $product->preparation_time_minutes
                : '',
            'application_tax' => $product->application_tax ?? 'none',
            'selling_price_tax_type' => $product->selling_price_tax_type ?? 'exclusive',
            'product_type' => $product->product_type,
            'single_dpp' => $decimal($product->single_dpp),
            'single_dpp_inc_tax' => $decimal($product->single_dpp_inc_tax),
            'profit_percent' => $decimal($product->profit_percent),
            'single_dsp' => $decimal($product->single_dsp),
            'single_dsp_inc_tax' => $decimal($product->single_dsp_inc_tax),
            'combo_lines' => is_array($comboLines) ? $comboLines : [],
            'combo_profit_percent' => $decimal($product->combo_profit_percent),
            'combo_selling_price' => $decimal($product->combo_selling_price),
            'combo_selling_price_inc_tax' => $decimal($product->combo_selling_price_inc_tax),
            'combo_purchase_total_exc_tax' => $decimal($product->combo_purchase_total_exc_tax),
            'combo_purchase_total_inc_tax' => $decimal($product->combo_purchase_total_inc_tax),
            'variation_sku_format' => $product->variation_sku_format ?? 'with_out_variation',
            'variation_matrix' => $product->variation_matrix ?? [],
        ];
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

    public function update(UpdateProductRequest $request, Team $current_team, Product $product): RedirectResponse
    {
        $this->productService->update(
            $product,
            $request->productPayload(),
            $request->file('product_image'),
            $request->file('product_brochure'),
            $request->openingStocks(),
        );

        return to_route('products.index', ['current_team' => $current_team])
            ->with('success', 'Product updated.');
    }

    public function destroy(Request $request, Team $current_team, Product $product): RedirectResponse
    {
        try {
            $this->productService->delete($product);
        } catch (QueryException) {
            return back()->with('error', 'This product cannot be deleted because it is referenced by sales, purchases, or other records.');
        }

        return to_route('products.index', ['current_team' => $current_team])
            ->with('success', 'Product deleted.');
    }

    public function search(Request $request, Team $current_team): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

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

        $categoryRaw = $request->query('category_id');
        $categoryId = is_numeric($categoryRaw) ? (int) $categoryRaw : null;
        if ($categoryId !== null && $categoryId < 1) {
            $categoryId = null;
        }

        $brandRaw = $request->query('brand_id');
        $brandId = is_numeric($brandRaw) ? (int) $brandRaw : null;
        if ($brandId !== null && $brandId < 1) {
            $brandId = null;
        }

        $rows = $this->productService
            ->searchQuery($current_team, $q, $sellableOnly, $businessLocationId, $categoryId, $brandId)
            ->get([
                'id',
                'name',
                'sku',
                'product_type',
                'single_dsp',
                'combo_selling_price',
                'category_id',
                'manage_stock',
                'image_path',
            ]);

        return response()->json([
            'data' => $rows->map(function (Product $p) {
                $defaultPrice = $p->product_type === 'combo'
                    ? ($p->combo_selling_price ?? null)
                    : ($p->single_dsp ?? null);

                $stockQty = $p->manage_stock
                    ? (string) ($p->location_stock_quantity ?? '0')
                    : null;

                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'sku' => $p->sku,
                    'text' => $p->name.($p->sku ? ' ('.$p->sku.')' : ''),
                    'default_unit_price' => $defaultPrice !== null ? (string) $defaultPrice : '0',
                    'category_id' => $p->category_id,
                    'category_name' => $p->category?->name,
                    'image_url' => $p->image_path
                        ? Storage::disk('public')->url($p->image_path)
                        : null,
                    'manage_stock' => (bool) $p->manage_stock,
                    'stock_quantity' => $stockQty,
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
