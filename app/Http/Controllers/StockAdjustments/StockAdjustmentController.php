<?php

namespace App\Http\Controllers\StockAdjustments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StockAdjustmentIndexRequest;
use App\Http\Requests\Inventory\StoreStockAdjustmentRequest;
use App\Models\BusinessLocation;
use App\Models\StockAdjustment;
use App\Models\Team;
use App\Services\StockAdjustmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StockAdjustmentController extends Controller
{
    public function __construct(
        protected StockAdjustmentService $stockAdjustmentService,
    ) {}

    public function index(StockAdjustmentIndexRequest $request, Team $current_team): Response
    {
        $filters = $request->filters();
        $query = StockAdjustment::query()
            ->forTeam($current_team)
            ->with('businessLocation');

        if (! empty($filters['search'])) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $filters['search']).'%';
            $query->where(function ($q) use ($term) {
                $q->where('ref_no', 'like', $term)
                    ->orWhere('adjustment_type', 'like', $term)
                    ->orWhereHas('businessLocation', fn ($l) => $l->where('name', 'like', $term));
            });
        }

        $sort = $filters['sort'] ?? 'transaction_date';
        $direction = strtolower((string) ($filters['direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSort = ['id', 'transaction_date', 'final_total', 'adjustment_type', 'total_amount_recovered', 'created_at'];
        $query->orderBy(
            in_array($sort, $allowedSort, true) ? $sort : 'transaction_date',
            $direction
        );

        $perPage = min(100, max(10, (int) ($filters['per_page'] ?? 15)));
        $paginator = $query->paginate($perPage)->withQueryString();
        $paginator->through(fn (StockAdjustment $a) => [
            'id' => $a->id,
            'ref_no' => $a->ref_no,
            'transaction_date' => $a->transaction_date?->toIso8601String(),
            'adjustment_type' => $a->adjustment_type,
            'final_total' => (string) $a->final_total,
            'total_amount_recovered' => (string) $a->total_amount_recovered,
            'business_location' => $a->businessLocation ? [
                'id' => $a->businessLocation->id,
                'name' => $a->businessLocation->name,
            ] : null,
        ]);

        return Inertia::render('inventory/stock-adjustments/Index', [
            'adjustments' => $paginator,
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

        return Inertia::render('inventory/stock-adjustments/Create', [
            'businessLocations' => $locations,
        ]);
    }

    public function store(StoreStockAdjustmentRequest $request, Team $current_team): RedirectResponse
    {
        try {
            $this->stockAdjustmentService->store(
                $current_team,
                $request->validated(),
                $request->user()?->id,
            );
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages([
                'lines' => [$e->getMessage()],
            ]);
        }

        return to_route('stock-adjustments.index', ['current_team' => $current_team])
            ->with('success', 'Stock adjustment saved.');
    }
}
