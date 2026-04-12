<?php

namespace App\Http\Controllers\Expenses;

use App\Http\Controllers\Controller;
use App\Http\Requests\Expenses\ExpenseIndexRequest;
use App\Http\Requests\Expenses\StoreExpenseRequest;
use App\Models\BusinessLocation;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentAccount;
use App\Models\TaxRate;
use App\Models\Team;
use App\Services\ExpenseService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function __construct(
        protected ExpenseService $expenseService,
    ) {}

    public function index(ExpenseIndexRequest $request, Team $current_team): Response
    {
        $filters = $request->filters();
        $query = Expense::query()
            ->forTeam($current_team)
            ->with(['businessLocation', 'expenseCategory', 'contact']);

        if (! empty($filters['search'])) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $filters['search']).'%';
            $query->where(function ($q) use ($term) {
                $q->where('ref_no', 'like', $term)
                    ->orWhereHas('businessLocation', fn ($l) => $l->where('name', 'like', $term))
                    ->orWhereHas('expenseCategory', fn ($c) => $c->where('name', 'like', $term))
                    ->orWhereHas('contact', function ($c) use ($term) {
                        $c->where('business_name', 'like', $term)
                            ->orWhere('first_name', 'like', $term)
                            ->orWhere('last_name', 'like', $term)
                            ->orWhere('customer_code', 'like', $term);
                    });
            });
        }

        $sort = $filters['sort'] ?? 'transaction_date';
        $direction = strtolower((string) ($filters['direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSort = ['id', 'transaction_date', 'final_total', 'ref_no', 'created_at'];
        $query->orderBy(
            in_array($sort, $allowedSort, true) ? $sort : 'transaction_date',
            $direction
        );

        $perPage = min(100, max(10, (int) ($filters['per_page'] ?? 15)));
        $paginator = $query->paginate($perPage)->withQueryString();
        $paginator->through(fn (Expense $e) => [
            'id' => $e->id,
            'ref_no' => $e->ref_no,
            'transaction_date' => $e->transaction_date?->toIso8601String(),
            'final_total' => (string) $e->final_total,
            'is_refund' => $e->is_refund,
            'business_location' => $e->businessLocation ? [
                'id' => $e->businessLocation->id,
                'name' => $e->businessLocation->name,
            ] : null,
            'expense_category' => $e->expenseCategory ? [
                'id' => $e->expenseCategory->id,
                'name' => $e->expenseCategory->name,
            ] : null,
            'contact' => $e->contact ? [
                'id' => $e->contact->id,
                'display_name' => $e->contact->display_name,
            ] : null,
        ]);

        return Inertia::render('expenses/Index', [
            'expenses' => $paginator,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'sort' => $filters['sort'] ?? 'transaction_date',
                'direction' => $filters['direction'] ?? 'desc',
                'per_page' => $filters['per_page'] ?? 15,
            ],
        ]);
    }

    public function create(Team $current_team): Response
    {
        $locations = BusinessLocation::query()
            ->forTeam($current_team)
            ->orderBy('name')
            ->get(['id', 'name']);

        $parentCategories = ExpenseCategory::query()
            ->forTeam($current_team)
            ->roots()
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $childCategories = ExpenseCategory::query()
            ->forTeam($current_team)
            ->whereNotNull('parent_id')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'parent_id']);

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

        $customers = Customer::query()
            ->forTeam($current_team)
            ->orderBy('business_name')
            ->orderBy('first_name')
            ->limit(500)
            ->get(['id', 'business_name', 'first_name', 'last_name', 'customer_code']);

        $customerRows = $customers->map(fn (Customer $c) => [
            'id' => $c->id,
            'display_name' => $c->display_name,
        ]);

        return Inertia::render('expenses/Create', [
            'businessLocations' => $locations,
            'expenseCategoryParents' => $parentCategories,
            'expenseCategoryChildren' => $childCategories,
            'taxRates' => $taxRates,
            'paymentAccounts' => $paymentAccounts,
            'paymentSettings' => $current_team->resolvedPaymentSettings(),
            'teamMembers' => $members->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
            ]),
            'customers' => $customerRows,
        ]);
    }

    public function store(StoreExpenseRequest $request, Team $current_team): RedirectResponse
    {
        $data = $request->validated();
        $document = $request->file('document');
        unset($data['document']);

        $this->expenseService->store(
            $current_team,
            $data,
            $document,
            $request->user()?->id,
        );

        return to_route('expenses.index', ['current_team' => $current_team])
            ->with('success', 'Expense saved.');
    }
}
