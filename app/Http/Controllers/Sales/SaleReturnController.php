<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\SaleReturnIndexRequest;
use App\Http\Requests\Sales\StoreSaleReturnRequest;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\Team;
use App\Services\SaleReturnService;
use App\Support\SaleListingPermissionScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SaleReturnController extends Controller
{
    public function __construct(
        protected SaleReturnService $saleReturnService,
    ) {}

    public function index(SaleReturnIndexRequest $request, Team $current_team): Response
    {
        $filters = $request->filters();
        $query = SaleReturn::query()
            ->forTeam($current_team)
            ->with(['parentSale.customer', 'parentSale.businessLocation']);

        $user = $request->user();
        if ($user && ! $user->ownsTeam($current_team)) {
            if (
                $user->hasPosPermission($current_team, 'access_own_sell_return')
                && ! $user->hasPosPermission($current_team, 'access_sell_return')
            ) {
                $query->where(function ($q) use ($user): void {
                    $q->where('sale_returns.created_by', $user->id)
                        ->orWhereHas('parentSale', fn ($ps) => $ps->where('created_by', $user->id));
                });
            }
        }

        if (! empty($filters['search'])) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $filters['search']).'%';
            $query->where(function ($q) use ($term) {
                $q->where('invoice_no', 'like', $term)
                    ->orWhereHas('parentSale', function ($ps) use ($term) {
                        $ps->where('invoice_no', 'like', $term)
                            ->orWhereHas('customer', function ($c) use ($term) {
                                $c->where('business_name', 'like', $term)
                                    ->orWhere('first_name', 'like', $term)
                                    ->orWhere('last_name', 'like', $term)
                                    ->orWhere('customer_code', 'like', $term);
                            })
                            ->orWhereHas('businessLocation', fn ($l) => $l->where('name', 'like', $term));
                    });
            });
        }

        $sort = $filters['sort'] ?? 'created_at';
        $direction = strtolower((string) ($filters['direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSort = ['id', 'transaction_date', 'total_return', 'invoice_no', 'created_at'];
        $query->orderBy(
            in_array($sort, $allowedSort, true) ? $sort : 'created_at',
            $direction
        );

        $perPage = min(100, max(10, (int) ($filters['per_page'] ?? 15)));
        $paginator = $query->paginate($perPage)->withQueryString();
        $paginator->through(fn (SaleReturn $r) => [
            'id' => $r->id,
            'invoice_no' => $r->invoice_no,
            'transaction_date' => $r->transaction_date?->toIso8601String(),
            'total_return' => (string) $r->total_return,
            'parent_sale' => $r->parentSale ? [
                'id' => $r->parentSale->id,
                'invoice_no' => $r->parentSale->invoice_no,
                'customer' => $r->parentSale->customer?->display_name,
                'business_location' => $r->parentSale->businessLocation?->name,
            ] : null,
        ]);

        return Inertia::render('sales/returns/Index', [
            'returns' => $paginator,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'sort' => $filters['sort'] ?? 'created_at',
                'direction' => $filters['direction'] ?? 'desc',
                'per_page' => $filters['per_page'] ?? 15,
            ],
        ]);
    }

    public function create(Request $request, Team $current_team): Response
    {
        $saleId = (int) $request->query('sale_id', 0);
        abort_if($saleId < 1, 404);

        $sale = Sale::query()
            ->forTeam($current_team)
            ->where('status', 'final')
            ->whereKey($saleId)
            ->with(['lines.product', 'customer', 'businessLocation'])
            ->firstOrFail();

        abort_unless(
            SaleListingPermissionScope::canAccessSellReturn($request->user(), $current_team, $sale),
            403,
        );

        $parent = [
            'id' => $sale->id,
            'invoice_no' => $sale->invoice_no,
            'transaction_date' => $sale->transaction_date?->toIso8601String(),
            'discount_type' => $sale->discount_type ?? 'none',
            'discount_amount' => (string) ($sale->discount_amount ?? '0'),
            'customer' => $sale->customer ? [
                'id' => $sale->customer->id,
                'display_name' => $sale->customer->display_name,
            ] : null,
            'business_location' => $sale->businessLocation ? [
                'id' => $sale->businessLocation->id,
                'name' => $sale->businessLocation->name,
            ] : null,
            'lines' => $sale->lines->map(fn ($line) => [
                'id' => $line->id,
                'product_name' => $line->product?->name ?? '—',
                'sku' => $line->product?->sku,
                'quantity' => (string) $line->quantity,
                'unit_price_exc_tax' => (string) $line->unit_price_exc_tax,
            ]),
        ];

        return Inertia::render('sales/returns/Create', [
            'parent' => $parent,
        ]);
    }

    public function store(StoreSaleReturnRequest $request, Team $current_team): RedirectResponse
    {
        $data = $request->validated();
        $parent = Sale::query()
            ->forTeam($current_team)
            ->whereKey((int) $data['parent_sale_id'])
            ->firstOrFail();

        abort_unless(
            SaleListingPermissionScope::canAccessSellReturn($request->user(), $current_team, $parent),
            403,
        );

        unset($data['parent_sale_id']);

        $this->saleReturnService->store($current_team, $parent, $data, auth()->id());

        return to_route('sales.returns.index', ['current_team' => $current_team])
            ->with('success', 'Sale return recorded and stock updated.');
    }
}
