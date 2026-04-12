<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use App\Http\Requests\Purchases\PurchaseIndexRequest;
use App\Http\Requests\Purchases\StorePurchaseRequest;
use App\Http\Resources\PurchaseResource;
use App\Models\BusinessLocation;
use App\Models\PaymentAccount;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\TaxRate;
use App\Models\Team;
use App\Services\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class PurchaseController extends Controller
{
    public function __construct(
        protected PurchaseService $purchaseService,
    ) {}

    public function index(PurchaseIndexRequest $request, Team $current_team): Response
    {
        $filters = $request->filters();
        $query = Purchase::query()
            ->forTeam($current_team)
            ->with(['supplier', 'businessLocation']);

        if (! empty($filters['search'])) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $filters['search']).'%';
            $query->where(function ($q) use ($term) {
                $q->where('ref_no', 'like', $term)
                    ->orWhere('status', 'like', $term)
                    ->orWhereHas('supplier', function ($s) use ($term) {
                        $s->where('business_name', 'like', $term)
                            ->orWhere('first_name', 'like', $term)
                            ->orWhere('last_name', 'like', $term)
                            ->orWhere('supplier_code', 'like', $term);
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
        $paginator->through(fn (Purchase $p) => (new PurchaseResource($p))->resolve());

        return Inertia::render('purchases/Index', [
            'purchases' => $paginator,
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
        $suppliers = Supplier::query()
            ->forTeam($current_team)
            ->orderBy('business_name')
            ->orderBy('first_name')
            ->get();

        $supplierRows = $suppliers->map(fn (Supplier $s) => [
            'id' => $s->id,
            'display_name' => $s->display_name,
            'address_line_1' => $s->address_line_1,
            'address_line_2' => $s->address_line_2,
            'city' => $s->city,
            'state' => $s->state,
            'zip_code' => $s->zip_code,
            'country' => $s->country,
        ]);

        $locations = BusinessLocation::query()
            ->forTeam($current_team)
            ->orderBy('name')
            ->get(['id', 'name']);

        $taxRates = TaxRate::query()
            ->forTeam($current_team)
            ->orderBy('name')
            ->get(['id', 'name', 'amount']);

        $paymentAccounts = PaymentAccount::query()
            ->forTeam($current_team)
            ->forPaymentSelection()
            ->where('is_active', true)
            ->orderBy('payment_method')
            ->orderBy('name')
            ->get(['id', 'name', 'payment_method']);

        $members = $current_team->members()
            ->orderBy('name')
            ->get(['users.id', 'users.name', 'users.email']);

        return Inertia::render('purchases/Create', [
            'suppliers' => $supplierRows,
            'businessLocations' => $locations,
            'taxRates' => $taxRates,
            'paymentAccounts' => $paymentAccounts,
            'paymentSettings' => $current_team->resolvedPaymentSettings(),
            'teamMembers' => $members->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
            ]),
        ]);
    }

    public function store(StorePurchaseRequest $request, Team $current_team): RedirectResponse
    {
        $data = $request->validated();
        $document = $request->file('document');
        unset($data['document']);

        $this->purchaseService->create($current_team, $data, $document);

        return to_route('purchases.index', ['current_team' => $current_team])
            ->with('success', 'Purchase saved.');
    }

    public function detail(Team $current_team, Purchase $purchase): JsonResponse
    {
        abort_unless($purchase->team_id === $current_team->id, SymfonyResponse::HTTP_NOT_FOUND);

        $purchase->load([
            'supplier',
            'businessLocation',
            'taxRate',
            'lines.product.unit',
            'payments.paymentAccount',
        ]);

        $paidTotal = (float) $purchase->payments->sum('amount');
        $finalTotal = (float) $purchase->final_total;
        if ($paidTotal + 0.0001 >= $finalTotal) {
            $paymentStatus = 'Paid';
        } elseif ($paidTotal > 0) {
            $paymentStatus = 'Partial';
        } else {
            $paymentStatus = 'Due';
        }

        $supplier = $purchase->supplier;

        return response()->json([
            'purchase' => [
                'id' => $purchase->id,
                'ref_no' => $purchase->ref_no,
                'transaction_date' => $purchase->transaction_date?->toIso8601String(),
                'status' => $purchase->status,
                'payment_status' => $paymentStatus,
                'pay_term_number' => $purchase->pay_term_number,
                'pay_term_type' => $purchase->pay_term_type,
                'discount_type' => $purchase->discount_type,
                'discount_amount' => (string) $purchase->discount_amount,
                'lines_total' => (string) $purchase->lines_total,
                'purchase_tax_amount' => (string) $purchase->purchase_tax_amount,
                'shipping_details' => $purchase->shipping_details,
                'shipping_charges' => (string) $purchase->shipping_charges,
                'additional_notes' => $purchase->additional_notes,
                'additional_expenses' => $purchase->additional_expenses ?? [],
                'final_total' => (string) $purchase->final_total,
                'supplier' => $supplier ? [
                    'display_name' => $supplier->display_name,
                    'business_name' => $supplier->business_name,
                    'first_name' => $supplier->first_name,
                    'last_name' => $supplier->last_name,
                    'tax_number' => $supplier->tax_number,
                    'mobile' => $supplier->mobile,
                    'email' => $supplier->email,
                    'address_line_1' => $supplier->address_line_1,
                    'address_line_2' => $supplier->address_line_2,
                    'city' => $supplier->city,
                    'state' => $supplier->state,
                    'country' => $supplier->country,
                    'zip_code' => $supplier->zip_code,
                ] : null,
                'business_location' => $purchase->businessLocation ? [
                    'name' => $purchase->businessLocation->name,
                    'landmark' => $purchase->businessLocation->landmark,
                    'city' => $purchase->businessLocation->city,
                    'state' => $purchase->businessLocation->state,
                    'country' => $purchase->businessLocation->country,
                    'zip_code' => $purchase->businessLocation->zip_code,
                    'mobile' => $purchase->businessLocation->mobile,
                    'email' => $purchase->businessLocation->email,
                ] : null,
                'tax_rate' => $purchase->taxRate ? [
                    'name' => $purchase->taxRate->name,
                    'amount' => (string) $purchase->taxRate->amount,
                ] : null,
                'lines' => $purchase->lines->map(fn ($line) => [
                    'id' => $line->id,
                    'product_name' => $line->product?->name ?? '—',
                    'sku' => $line->product?->sku,
                    'quantity' => (string) $line->quantity,
                    'unit_short_name' => $line->product?->unit?->short_name ?? $line->product?->unit?->name ?? '',
                    'unit_cost_before_discount' => (string) $line->unit_cost_before_discount,
                    'discount_percent' => (string) $line->discount_percent,
                    'unit_cost_exc_tax' => (string) $line->unit_cost_exc_tax,
                    'line_subtotal_exc_tax' => (string) $line->line_subtotal_exc_tax,
                    'product_tax_percent' => (string) $line->product_tax_percent,
                    'line_tax_amount' => (string) $line->line_tax_amount,
                    'line_total' => (string) $line->line_total,
                    'unit_cost_inc_tax' => (string) round(
                        (float) $line->quantity > 0
                            ? (float) $line->line_total / (float) $line->quantity
                            : 0.0,
                        4
                    ),
                ]),
                'payments' => $purchase->payments->map(fn ($p) => [
                    'id' => $p->id,
                    'amount' => (string) $p->amount,
                    'paid_on' => $p->paid_on?->toIso8601String(),
                    'method' => $p->method,
                    'note' => $p->note,
                    'payment_account' => $p->paymentAccount?->name,
                ]),
                'activities' => [],
            ],
        ]);
    }
}
