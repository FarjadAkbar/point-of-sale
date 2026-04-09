<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Concerns\BuildsSaleFormPageProps;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\SaleIndexRequest;
use App\Http\Requests\Sales\StoreDraftSaleRequest;
use App\Http\Requests\Sales\StoreQuotationSaleRequest;
use App\Http\Requests\Sales\StoreSaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\Sale;
use App\Models\Team;
use App\Services\SaleService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

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

        return Inertia::render('sales/Index', [
            'sales' => $paginator,
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
}
