<?php

namespace App\Services;

use App\Models\StockAdjustment;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockAdjustmentService
{
    public function __construct(
        protected ProductStockService $productStockService,
    ) {}

    /**
     * @param  array{business_location_id: int, ref_no?: string|null, transaction_date: string, adjustment_type: string, total_amount_recovered: float|int|string, additional_notes?: string|null, lines: list<array{product_id: int, quantity: float|int|string, unit_price: float|int|string}>}  $data
     */
    public function store(Team $team, array $data, ?int $userId): StockAdjustment
    {
        return DB::transaction(function () use ($team, $data, $userId) {
            $locationId = (int) $data['business_location_id'];
            $linesInput = $data['lines'];
            $lineRows = [];
            $linesSum = 0.0;

            foreach ($linesInput as $row) {
                $qty = round((float) ($row['quantity'] ?? 0), 4);
                if ($qty == 0.0) {
                    throw ValidationException::withMessages([
                        'lines' => ['Each line must have a non-zero quantity.'],
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

            $finalTotal = round($linesSum, 4);

            $adjustment = StockAdjustment::query()->create([
                'team_id' => $team->id,
                'business_location_id' => $locationId,
                'ref_no' => $data['ref_no'] ?? null,
                'transaction_date' => $data['transaction_date'],
                'adjustment_type' => $data['adjustment_type'],
                'total_amount_recovered' => round((float) ($data['total_amount_recovered'] ?? 0), 4),
                'additional_notes' => $data['additional_notes'] ?? null,
                'final_total' => $finalTotal,
                'created_by' => $userId,
            ]);

            foreach ($lineRows as $lr) {
                $adjustment->lines()->create($lr);
            }

            $adjustment->load('lines.product');

            foreach ($adjustment->lines as $line) {
                $product = $line->product;
                if (! $product || $product->team_id !== $team->id || ! $product->manage_stock) {
                    continue;
                }

                $q = (float) $line->quantity;
                if ($q > 0) {
                    $this->productStockService->addQuantity($product->id, $locationId, $q);
                } elseif ($q < 0) {
                    $this->productStockService->subtractQuantity($product->id, $locationId, abs($q));
                }
            }

            return $adjustment;
        });
    }
}
