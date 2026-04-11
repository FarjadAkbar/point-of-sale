<?php

namespace App\Services;

use App\Models\StockTransfer;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockTransferService
{
    public function __construct(
        protected ProductStockService $productStockService,
    ) {}

    /**
     * @param  array{transaction_date: string, ref_no?: string|null, status: string, from_business_location_id: int, to_business_location_id: int, shipping_charges: float|int|string, additional_notes?: string|null, lines: list<array{product_id: int, quantity: float|int|string, unit_price: float|int|string}>}  $data
     */
    public function store(Team $team, array $data, ?int $userId): StockTransfer
    {
        if ((int) $data['from_business_location_id'] === (int) $data['to_business_location_id']) {
            throw ValidationException::withMessages([
                'to_business_location_id' => ['Location (to) must differ from location (from).'],
            ]);
        }

        return DB::transaction(function () use ($team, $data, $userId) {
            $linesInput = $data['lines'];
            $lineRows = [];
            $linesSum = 0.0;

            foreach ($linesInput as $row) {
                $qty = round((float) ($row['quantity'] ?? 0), 4);
                if ($qty <= 0) {
                    throw ValidationException::withMessages([
                        'lines' => ['Each line must have a quantity greater than zero.'],
                    ]);
                }

                $unit = round((float) ($row['unit_price'] ?? 0), 4);
                $sub = round($qty * $unit, 4);
                $linesSum += $sub;

                $lineRows[] = [
                    'product_id' => (int) $row['product_id'],
                    'quantity' => $qty,
                    'unit_price' => $unit,
                    'line_subtotal' => $sub,
                ];
            }

            $shipping = round((float) ($data['shipping_charges'] ?? 0), 4);
            $finalTotal = round($linesSum + $shipping, 4);

            $transfer = StockTransfer::query()->create([
                'team_id' => $team->id,
                'transaction_date' => $data['transaction_date'],
                'ref_no' => $data['ref_no'] ?? null,
                'status' => $data['status'],
                'from_business_location_id' => (int) $data['from_business_location_id'],
                'to_business_location_id' => (int) $data['to_business_location_id'],
                'shipping_charges' => $shipping,
                'additional_notes' => $data['additional_notes'] ?? null,
                'final_total' => $finalTotal,
                'created_by' => $userId,
            ]);

            foreach ($lineRows as $lr) {
                $transfer->lines()->create($lr);
            }

            $transfer->load('lines.product');

            if ($transfer->status === 'completed') {
                $this->applyCompletedTransfer($team, $transfer);
            }

            return $transfer;
        });
    }

    protected function applyCompletedTransfer(Team $team, StockTransfer $transfer): void
    {
        $from = (int) $transfer->from_business_location_id;
        $to = (int) $transfer->to_business_location_id;

        foreach ($transfer->lines as $line) {
            $product = $line->product;
            if (! $product || $product->team_id !== $team->id || ! $product->manage_stock) {
                continue;
            }

            $qty = (float) $line->quantity;
            $this->productStockService->subtractQuantity($product->id, $from, $qty);
            $this->productStockService->addQuantity($product->id, $to, $qty);
        }
    }
}
