<?php

namespace App\Http\Controllers\StockTransfers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreStockTransferRequest;
use App\Models\BusinessLocation;
use App\Models\StockTransfer;
use App\Models\Team;
use App\Services\StockTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StockTransferController extends Controller
{
    public function __construct(
        protected StockTransferService $stockTransferService,
    ) {}

    public function index(Team $current_team): Response
    {
        $transfers = StockTransfer::query()
            ->forTeam($current_team)
            ->with(['fromLocation', 'toLocation'])
            ->orderByDesc('transaction_date')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (StockTransfer $t) => [
                'id' => $t->id,
                'ref_no' => $t->ref_no,
                'transaction_date' => $t->transaction_date?->toIso8601String(),
                'status' => $t->status,
                'final_total' => (string) $t->final_total,
                'from_location' => $t->fromLocation ? [
                    'id' => $t->fromLocation->id,
                    'name' => $t->fromLocation->name,
                ] : null,
                'to_location' => $t->toLocation ? [
                    'id' => $t->toLocation->id,
                    'name' => $t->toLocation->name,
                ] : null,
            ]);

        return Inertia::render('inventory/stock-transfers/Index', [
            'transfers' => $transfers,
        ]);
    }

    public function create(Team $current_team): Response
    {
        $locations = BusinessLocation::query()
            ->forTeam($current_team)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('inventory/stock-transfers/Create', [
            'businessLocations' => $locations,
        ]);
    }

    public function store(StoreStockTransferRequest $request, Team $current_team): RedirectResponse
    {
        try {
            $this->stockTransferService->store(
                $current_team,
                $request->validated(),
                $request->user()?->id,
            );
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages([
                'lines' => [$e->getMessage()],
            ]);
        }

        return to_route('stock-transfers.index', ['current_team' => $current_team])
            ->with('success', 'Stock transfer saved.');
    }
}
