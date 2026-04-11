<?php

namespace App\Services;

use App\Models\BusinessLocation;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class ProductStockService
{
    /**
     * Set opening quantities when creating a product (only for selected locations, non-negative).
     *
     * @param  list<array{business_location_id: int, quantity: float|int|string}>  $rows
     */
    public function setOpeningStocks(Product $product, array $rows): void
    {
        if (! $product->manage_stock) {
            return;
        }

        $allowed = collect($product->business_location_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->all();

        DB::transaction(function () use ($product, $rows, $allowed) {
            foreach ($rows as $row) {
                $locId = (int) ($row['business_location_id'] ?? 0);
                $qty = round((float) ($row['quantity'] ?? 0), 4);

                if ($qty <= 0 || ! in_array($locId, $allowed, true)) {
                    continue;
                }

                if (! BusinessLocation::query()->where('team_id', $product->team_id)->whereKey($locId)->exists()) {
                    continue;
                }

                ProductStock::query()->updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'business_location_id' => $locId,
                    ],
                    ['quantity' => $qty],
                );
            }
        });
    }

    /**
     * Increase stock for each line when a purchase is marked received.
     */
    public function applyPurchaseReceived(Purchase $purchase): void
    {
        if ($purchase->status !== 'received') {
            return;
        }

        $locationId = (int) $purchase->business_location_id;

        if (! BusinessLocation::query()->where('team_id', $purchase->team_id)->whereKey($locationId)->exists()) {
            return;
        }

        $purchase->loadMissing('lines.product');

        foreach ($purchase->lines as $line) {
            $product = $line->product;
            if (! $product || ! $product->manage_stock) {
                continue;
            }

            $delta = (float) $line->quantity;
            if ($delta <= 0) {
                continue;
            }

            $this->addQuantity($product->id, $locationId, $delta);
        }
    }

    public function quantityAt(int $productId, int $businessLocationId): float
    {
        $row = ProductStock::query()
            ->where('product_id', $productId)
            ->where('business_location_id', $businessLocationId)
            ->first();

        return $row ? (float) $row->quantity : 0.0;
    }

    /**
     * Decrease stock for each line when a sale is final (must run inside a transaction).
     */
    public function applySaleFinal(Sale $sale): void
    {
        if ($sale->status !== 'final') {
            return;
        }

        $locationId = (int) $sale->business_location_id;

        if (! BusinessLocation::query()->where('team_id', $sale->team_id)->whereKey($locationId)->exists()) {
            return;
        }

        $sale->loadMissing('lines.product');

        foreach ($sale->lines as $line) {
            $product = $line->product;
            if (! $product || ! $product->manage_stock) {
                continue;
            }

            $qty = (float) $line->quantity;
            if ($qty <= 0) {
                continue;
            }

            $this->subtractQuantity($product->id, $locationId, $qty);
        }
    }

    /**
     * Put quantities back when a final sale is voided or adjusted (inside a transaction).
     */
    public function revertSaleFinal(Sale $sale): void
    {
        if ($sale->status !== 'final') {
            return;
        }

        $locationId = (int) $sale->business_location_id;

        if (! BusinessLocation::query()->where('team_id', $sale->team_id)->whereKey($locationId)->exists()) {
            return;
        }

        $sale->loadMissing('lines.product');

        foreach ($sale->lines as $line) {
            $product = $line->product;
            if (! $product || ! $product->manage_stock) {
                continue;
            }

            $qty = (float) $line->quantity;
            if ($qty <= 0) {
                continue;
            }

            $this->addQuantity($product->id, $locationId, $qty);
        }
    }

    /**
     * Must run inside an outer database transaction when used with concurrent writers.
     */
    public function subtractQuantity(int $productId, int $businessLocationId, float $qty): void
    {
        if ($qty <= 0) {
            return;
        }

        $row = ProductStock::query()
            ->where('product_id', $productId)
            ->where('business_location_id', $businessLocationId)
            ->lockForUpdate()
            ->first();

        $current = $row ? (float) $row->quantity : 0.0;
        if ($current < $qty) {
            throw new \RuntimeException('Insufficient stock for one or more products at this location.');
        }

        if ($row) {
            $row->quantity = round($current - $qty, 4);
            $row->save();
        }
    }

    /**
     * Add quantity at a location (creates row if missing).
     * Must run inside an outer database transaction when used with concurrent writers.
     */
    public function addQuantity(int $productId, int $businessLocationId, float $delta): void
    {
        if ($delta == 0.0) {
            return;
        }

        $row = ProductStock::query()
            ->where('product_id', $productId)
            ->where('business_location_id', $businessLocationId)
            ->lockForUpdate()
            ->first();

        if ($row) {
            $row->quantity = round((float) $row->quantity + $delta, 4);
            $row->save();
        } else {
            ProductStock::query()->create([
                'product_id' => $productId,
                'business_location_id' => $businessLocationId,
                'quantity' => round($delta, 4),
            ]);
        }
    }
}
