<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleReturnService
{
    public function __construct(
        protected ProductStockService $productStockService,
        protected SaleService $saleService,
    ) {}

    /**
     * @param  array{invoice_no?: string|null, transaction_date: string, discount_type: string, discount_amount: float|int|string, lines: list<array{sale_line_id: int, quantity: float|int|string}>}  $data
     */
    public function store(Team $team, Sale $parent, array $data, ?int $userId): SaleReturn
    {
        if ($parent->team_id !== $team->id) {
            abort(403);
        }

        if ($parent->status !== 'final') {
            throw ValidationException::withMessages([
                'parent_sale_id' => ['Returns are only allowed for completed sales.'],
            ]);
        }

        $parent->loadMissing('lines.product');

        return DB::transaction(function () use ($team, $parent, $data, $userId) {
            $linesInput = $data['lines'] ?? [];
            $lineRows = [];
            $sum = 0.0;

            foreach ($linesInput as $row) {
                $qty = round((float) ($row['quantity'] ?? 0), 4);
                if ($qty <= 0) {
                    continue;
                }

                $saleLine = $parent->lines->firstWhere('id', (int) ($row['sale_line_id'] ?? 0));
                if (! $saleLine) {
                    throw ValidationException::withMessages([
                        'lines' => ['Invalid sale line.'],
                    ]);
                }

                if ($qty > (float) $saleLine->quantity) {
                    throw ValidationException::withMessages([
                        'lines' => ['Return quantity cannot exceed sold quantity for '.$saleLine->product?->name.'.'],
                    ]);
                }

                $uExc = (float) $saleLine->unit_price_exc_tax;
                $lineTotal = round($qty * $uExc, 4);
                $sum += $lineTotal;

                $lineRows[] = [
                    'sale_line_id' => $saleLine->id,
                    'product_id' => $saleLine->product_id,
                    'quantity' => $qty,
                    'unit_price_exc_tax' => $uExc,
                    'line_total' => $lineTotal,
                ];
            }

            if ($lineRows === []) {
                throw ValidationException::withMessages([
                    'lines' => ['Enter at least one return quantity greater than zero.'],
                ]);
            }

            $afterDiscount = $this->saleService->applyHeaderDiscount(
                $sum,
                (string) ($data['discount_type'] ?? 'none'),
                (float) ($data['discount_amount'] ?? 0),
            );

            $return = SaleReturn::query()->create([
                'team_id' => $team->id,
                'parent_sale_id' => $parent->id,
                'invoice_no' => $data['invoice_no'] ?? null,
                'transaction_date' => $data['transaction_date'],
                'discount_type' => $data['discount_type'] ?? 'none',
                'discount_amount' => round((float) ($data['discount_amount'] ?? 0), 4),
                'total_return' => round($afterDiscount, 4),
                'created_by' => $userId,
            ]);

            foreach ($lineRows as $lr) {
                $return->lines()->create($lr);
                $product = $parent->lines->firstWhere('id', $lr['sale_line_id'])?->product;
                if ($product && $product->manage_stock) {
                    $this->productStockService->addQuantity(
                        (int) $lr['product_id'],
                        (int) $parent->business_location_id,
                        (float) $lr['quantity'],
                    );
                }
            }

            return $return->load('lines');
        });
    }
}
