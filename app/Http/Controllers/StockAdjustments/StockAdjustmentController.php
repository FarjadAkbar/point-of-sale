<?php

namespace App\Http\Controllers\StockAdjustments;

use App\Http\Controllers\Controller;
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

    public function index(Team $current_team): Response
    {
        $adjustments = StockAdjustment::query()
            ->forTeam($current_team)
            ->with('businessLocation')
            ->orderByDesc('transaction_date')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (StockAdjustment $a) => [
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
            'adjustments' => $adjustments,
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
