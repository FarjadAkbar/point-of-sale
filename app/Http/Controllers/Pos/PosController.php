<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Concerns\BuildsSaleFormPageProps;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\PosListRequest;
use App\Http\Requests\Sales\StoreSaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\Brand;
use App\Models\CashRegisterSession;
use App\Models\Customer;
use App\Models\ProductCategory;
use App\Models\Sale;
use App\Models\Team;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PosController extends Controller
{
    use BuildsSaleFormPageProps;

    public function __construct(
        protected SaleService $saleService,
    ) {}

    public function index(Request $request, Team $current_team): Response
    {
        $props = $this->saleFormPageProps($current_team, false);
        unset($props['isDraftSale']);

        $props['productCategories'] = ProductCategory::query()
            ->forTeam($current_team)
            ->orderBy('name')
            ->get(['id', 'name']);

        $props['brands'] = Brand::query()
            ->forTeam($current_team)
            ->orderBy('name')
            ->get(['id', 'name']);

        $session = CashRegisterSession::query()
            ->forTeam($current_team)
            ->open()
            ->where('user_id', Auth::id())
            ->with('businessLocation:id,name')
            ->first();

        $props['cashRegisterSession'] = $session ? [
            'id' => $session->id,
            'business_location_id' => $session->business_location_id,
            'location_name' => $session->businessLocation?->name ?? '',
            'opening_cash' => (string) $session->opening_cash,
            'opened_at' => $session->opened_at?->toIso8601String() ?? '',
        ] : null;

        $props['posPrintSaleId'] = $request->session()->pull('pos_print_sale_id');

        return Inertia::render('pos/Index', $props);
    }

    public function recentTransactions(Team $current_team): JsonResponse
    {
        $mapSale = fn (Sale $s) => [
            'id' => $s->id,
            'invoice_no' => $s->invoice_no,
            'transaction_date' => $s->transaction_date?->toIso8601String(),
            'final_total' => (string) $s->final_total,
            'status' => $s->status,
            'customer' => $s->customer ? [
                'display_name' => $s->customer->display_name,
            ] : null,
        ];

        $final = Sale::query()
            ->forTeam($current_team)
            ->where('status', 'final')
            ->with('customer:id,business_name,first_name,last_name')
            ->orderByDesc('id')
            ->limit(30)
            ->get()
            ->map($mapSale)
            ->values()
            ->all();

        $quotation = Sale::query()
            ->forTeam($current_team)
            ->where('status', 'quotation')
            ->with('customer:id,business_name,first_name,last_name')
            ->orderByDesc('id')
            ->limit(30)
            ->get()
            ->map($mapSale)
            ->values()
            ->all();

        $draft = Sale::query()
            ->forTeam($current_team)
            ->where('status', 'draft')
            ->with('customer:id,business_name,first_name,last_name')
            ->orderByDesc('id')
            ->limit(30)
            ->get()
            ->map($mapSale)
            ->values()
            ->all();

        return response()->json([
            'final' => $final,
            'quotation' => $quotation,
            'draft' => $draft,
        ]);
    }

    public function list(PosListRequest $request, Team $current_team): Response
    {
        $filters = $request->filters();
        $date = ! empty($filters['date'])
            ? $filters['date']
            : now()->toDateString();

        $query = Sale::query()
            ->forTeam($current_team)
            ->where('status', 'final')
            ->whereDate('transaction_date', $date)
            ->with(['customer', 'businessLocation']);

        if (! empty($filters['search'])) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $filters['search']).'%';
            $query->where(function ($q) use ($term) {
                $q->where('invoice_no', 'like', $term)
                    ->orWhereHas('customer', function ($c) use ($term) {
                        $c->where('business_name', 'like', $term)
                            ->orWhere('first_name', 'like', $term)
                            ->orWhere('last_name', 'like', $term)
                            ->orWhere('mobile', 'like', $term)
                            ->orWhere('customer_code', 'like', $term);
                    });
            });
        }

        $sort = $filters['sort'] ?? 'created_at';
        $direction = strtolower((string) ($filters['direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy(
            in_array($sort, ['id', 'transaction_date', 'final_total', 'status', 'created_at'], true) ? $sort : 'created_at',
            $direction
        );

        $perPage = min(100, max(10, (int) ($filters['per_page'] ?? 15)));
        $paginator = $query->paginate($perPage)->withQueryString();
        $paginator->through(fn (Sale $s) => (new SaleResource($s))->resolve());

        $customers = Customer::query()
            ->forTeam($current_team)
            ->orderBy('business_name')
            ->orderBy('first_name')
            ->get(['id', 'business_name', 'first_name', 'last_name'])
            ->map(fn (Customer $c) => [
                'id' => $c->id,
                'display_name' => $c->display_name,
            ]);

        return Inertia::render('pos/List', [
            'sales' => $paginator,
            'customers' => $customers,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'date' => $date,
                'sort' => $filters['sort'] ?? 'created_at',
                'direction' => $filters['direction'] ?? 'desc',
                'per_page' => $filters['per_page'] ?? 15,
            ],
        ]);
    }

    public function checkout(StoreSaleRequest $request, Team $current_team): RedirectResponse
    {
        $data = $request->validated();
        $document = $request->file('document');
        unset($data['document']);

        $sale = $this->saleService->create($current_team, $data, $document);

        return to_route('pos.index', ['current_team' => $current_team])
            ->with('success', 'Sale completed.')
            ->with('pos_print_sale_id', $sale->id);
    }
}
