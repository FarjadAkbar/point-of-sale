<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\StoreSaleReturnRequest;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\Team;
use App\Services\SaleReturnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SaleReturnController extends Controller
{
    public function __construct(
        protected SaleReturnService $saleReturnService,
    ) {}

    public function index(Request $request, Team $current_team): Response
    {
        $returns = SaleReturn::query()
            ->forTeam($current_team)
            ->with(['parentSale.customer', 'parentSale.businessLocation'])
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (SaleReturn $r) => [
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
            'returns' => $returns,
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

        unset($data['parent_sale_id']);

        $this->saleReturnService->store($current_team, $parent, $data, auth()->id());

        return to_route('sales.returns.index', ['current_team' => $current_team])
            ->with('success', 'Sale return recorded and stock updated.');
    }
}
