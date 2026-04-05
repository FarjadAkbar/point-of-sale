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
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

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
}
