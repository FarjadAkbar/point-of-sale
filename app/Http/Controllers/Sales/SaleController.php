<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Concerns\BuildsSaleFormPageProps;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\SaleIndexRequest;
use App\Http\Requests\Sales\StoreDraftSaleRequest;
use App\Http\Requests\Sales\StoreQuotationSaleRequest;
use App\Http\Requests\Sales\StoreSaleRequest;
use App\Http\Requests\Sales\UpdateSaleRequest;
use App\Http\Requests\Sales\UpdateSaleShippingRequest;
use App\Http\Resources\SaleResource;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleActivity;
use App\Models\Team;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class SaleController extends Controller
{
    use BuildsSaleFormPageProps;

    public function __construct(
        protected SaleService $saleService,
    ) {}

    public function index(SaleIndexRequest $request, Team $current_team): Response
    {
        $filters = $request->filters();
        $query = Sale::query()
            ->forTeam($current_team)
            ->whereNot('status', 'draft')
            ->whereNot('status', 'quotation')
            ->with(['customer', 'businessLocation']);

        if (! empty($filters['search'])) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $filters['search']).'%';
            $query->where(function ($q) use ($term) {
                $q->where('invoice_no', 'like', $term)
                    ->orWhere('status', 'like', $term)
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

        return Inertia::render('sales/Index', [
            'sales' => $paginator,
            'customers' => $customers,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'sort' => $filters['sort'] ?? 'created_at',
                'direction' => $filters['direction'] ?? 'desc',
                'per_page' => $filters['per_page'] ?? 15,
            ],
        ]);
    }

    public function draftsIndex(SaleIndexRequest $request, Team $current_team): Response
    {
        $filters = $request->filters();
        $query = Sale::query()
            ->forTeam($current_team)
            ->where('status', 'draft')
            ->with(['customer', 'businessLocation']);

        if (! empty($filters['search'])) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $filters['search']).'%';
            $query->where(function ($q) use ($term) {
                $q->where('invoice_no', 'like', $term)
                    ->orWhere('status', 'like', $term)
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

        return Inertia::render('sales/drafts/Index', [
            'sales' => $paginator,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'sort' => $filters['sort'] ?? 'created_at',
                'direction' => $filters['direction'] ?? 'desc',
                'per_page' => $filters['per_page'] ?? 15,
            ],
        ]);
    }

    public function quotationsIndex(SaleIndexRequest $request, Team $current_team): Response
    {
        $filters = $request->filters();
        $query = Sale::query()
            ->forTeam($current_team)
            ->where('status', 'quotation')
            ->with(['customer', 'businessLocation']);

        if (! empty($filters['search'])) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $filters['search']).'%';
            $query->where(function ($q) use ($term) {
                $q->where('invoice_no', 'like', $term)
                    ->orWhere('status', 'like', $term)
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

        return Inertia::render('sales/quotations/Index', [
            'sales' => $paginator,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'sort' => $filters['sort'] ?? 'created_at',
                'direction' => $filters['direction'] ?? 'desc',
                'per_page' => $filters['per_page'] ?? 15,
            ],
        ]);
    }

    public function create(Team $current_team): Response
    {
        return Inertia::render('sales/Create', $this->saleFormPageProps($current_team, false));
    }

    public function createDraft(Team $current_team): Response
    {
        return Inertia::render('sales/Create', $this->saleFormPageProps($current_team, true));
    }

    public function createQuotation(Team $current_team): Response
    {
        return Inertia::render('sales/Create', $this->saleFormPageProps($current_team, false, true));
    }

    public function store(StoreSaleRequest $request, Team $current_team): RedirectResponse
    {
        $data = $request->validated();
        $document = $request->file('document');
        unset($data['document']);

        $this->saleService->create($current_team, $data, $document);

        return to_route('sales.index', ['current_team' => $current_team])
            ->with('success', 'Sale saved.');
    }

    public function storeDraft(StoreDraftSaleRequest $request, Team $current_team): RedirectResponse
    {
        $data = $request->validated();
        $document = $request->file('document');
        unset($data['document']);

        $this->saleService->create($current_team, $data, $document);

        return to_route('sales.drafts.index', ['current_team' => $current_team])
            ->with('success', 'Draft saved.');
    }

    public function storeQuotation(StoreQuotationSaleRequest $request, Team $current_team): RedirectResponse
    {
        $data = $request->validated();
        $document = $request->file('document');
        unset($data['document']);

        $this->saleService->create($current_team, $data, $document);

        return to_route('sales.quotations.index', ['current_team' => $current_team])
            ->with('success', 'Quotation saved.');
    }

    public function detail(Team $current_team, Sale $sale): JsonResponse
    {
        abort_unless($sale->team_id === $current_team->id, SymfonyResponse::HTTP_NOT_FOUND);

        $sale->load([
            'customer',
            'businessLocation',
            'taxRate',
            'lines.product',
            'payments.paymentAccount',
            'activities.user',
            'createdBy',
        ]);

        $paidTotal = $sale->payments->sum('amount');

        return response()->json([
            'sale' => [
                'id' => $sale->id,
                'invoice_no' => $sale->invoice_no,
                'transaction_date' => $sale->transaction_date?->toIso8601String(),
                'status' => $sale->status,
                'payment_status' => bccomp((string) $paidTotal, (string) $sale->final_total, 4) >= 0 ? 'paid' : 'due',
                'created_by_user' => $sale->createdBy ? [
                    'id' => $sale->createdBy->id,
                    'name' => $sale->createdBy->name,
                ] : null,
                'final_total' => (string) $sale->final_total,
                'lines_total' => (string) $sale->lines_total,
                'sale_note' => $sale->sale_note,
                'discount_type' => $sale->discount_type,
                'discount_amount' => (string) $sale->discount_amount,
                'sale_tax_amount' => (string) $sale->sale_tax_amount,
                'shipping_details' => $sale->shipping_details,
                'shipping_charges' => (string) $sale->shipping_charges,
                'shipping_address' => $sale->shipping_address,
                'shipping_status' => $sale->shipping_status,
                'delivered_to' => $sale->delivered_to,
                'delivery_person' => $sale->delivery_person,
                'shipping_customer_note' => $sale->shipping_customer_note,
                'shipping_document_path' => $sale->shipping_document_path,
                'document_path' => $sale->document_path,
                'customer' => $sale->customer ? [
                    'id' => $sale->customer->id,
                    'display_name' => $sale->customer->display_name,
                ] : null,
                'business_location' => $sale->businessLocation ? [
                    'id' => $sale->businessLocation->id,
                    'name' => $sale->businessLocation->name,
                ] : null,
                'tax_rate' => $sale->taxRate ? [
                    'id' => $sale->taxRate->id,
                    'name' => $sale->taxRate->name,
                    'amount' => (string) $sale->taxRate->amount,
                ] : null,
                'lines' => $sale->lines->map(fn ($line) => [
                    'id' => $line->id,
                    'product_id' => $line->product_id,
                    'product_name' => $line->product?->name ?? '—',
                    'sku' => $line->product?->sku,
                    'quantity' => (string) $line->quantity,
                    'unit_price_before_discount' => (string) $line->unit_price_before_discount,
                    'discount_percent' => (string) $line->discount_percent,
                    'unit_price_exc_tax' => (string) $line->unit_price_exc_tax,
                    'product_tax_percent' => (string) $line->product_tax_percent,
                    'line_total' => (string) $line->line_total,
                ]),
                'payments' => $sale->payments->map(fn ($p) => [
                    'id' => $p->id,
                    'amount' => (string) $p->amount,
                    'paid_on' => $p->paid_on?->toIso8601String(),
                    'method' => $p->method,
                    'note' => $p->note,
                    'payment_account' => $p->paymentAccount?->name,
                ]),
                'activities' => $sale->activities->map(fn ($a) => [
                    'id' => $a->id,
                    'action' => $a->action,
                    'note' => $a->note,
                    'by' => $a->user?->name,
                    'date' => $a->created_at?->toIso8601String(),
                ]),
            ],
        ]);
    }

    public function update(UpdateSaleRequest $request, Team $current_team, Sale $sale): RedirectResponse
    {
        abort_unless($sale->team_id === $current_team->id, SymfonyResponse::HTTP_NOT_FOUND);

        $sale->update($request->validated());

        SaleActivity::query()->create([
            'sale_id' => $sale->id,
            'user_id' => $request->user()?->id,
            'action' => 'sale_updated',
            'note' => null,
        ]);

        return back()->with('success', 'Sale updated.');
    }

    public function updateShipping(UpdateSaleShippingRequest $request, Team $current_team, Sale $sale): RedirectResponse
    {
        abort_unless($sale->team_id === $current_team->id, SymfonyResponse::HTTP_NOT_FOUND);

        $this->saleService->updateShipping(
            $current_team,
            $sale,
            $request->safe()->except('shipping_document'),
            $request->file('shipping_document'),
        );

        return back()->with('success', 'Shipping details saved.');
    }

    public function destroy(Team $current_team, Sale $sale): RedirectResponse
    {
        abort_unless($sale->team_id === $current_team->id, SymfonyResponse::HTTP_NOT_FOUND);

        try {
            $this->saleService->deleteSale($current_team, $sale);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->with('error', 'Could not delete sale.');
        }

        return to_route('sales.index', ['current_team' => $current_team])
            ->with('success', 'Sale deleted.');
    }

    public function printInvoice(Request $request, Team $current_team, Sale $sale): View
    {
        abort_unless($sale->team_id === $current_team->id, SymfonyResponse::HTTP_NOT_FOUND);
        $sale->load(['customer', 'businessLocation', 'lines.product', 'payments']);

        return view('sales.prints.invoice', [
            'sale' => $sale,
            'title' => 'Invoice',
        ]);
    }

    public function printPackingSlip(Request $request, Team $current_team, Sale $sale): View
    {
        abort_unless($sale->team_id === $current_team->id, SymfonyResponse::HTTP_NOT_FOUND);
        $sale->load(['customer', 'businessLocation', 'lines.product']);

        return view('sales.prints.packing-slip', [
            'sale' => $sale,
            'title' => 'Packing slip',
        ]);
    }

    public function printDeliveryNote(Request $request, Team $current_team, Sale $sale): View
    {
        abort_unless($sale->team_id === $current_team->id, SymfonyResponse::HTTP_NOT_FOUND);
        $sale->load(['customer', 'businessLocation', 'lines.product', 'payments']);

        return view('sales.prints.delivery-note', [
            'sale' => $sale,
            'title' => 'Delivery note',
        ]);
    }

    public function invoiceLink(Request $request, Team $current_team, Sale $sale): JsonResponse
    {
        abort_unless($sale->team_id === $current_team->id, SymfonyResponse::HTTP_NOT_FOUND);

        $url = URL::route('sales.documents.invoice', [
            'current_team' => $current_team->slug,
            'sale' => $sale->id,
        ]);

        return response()->json(['url' => $url]);
    }
}
