<?php

namespace App\Services;

use App\Models\PurchaseLine;
use App\Models\SaleLine;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ItemsReportService
{
    /**
     * @return array{rows: list<array<string, mixed>>, footer: array<string, string>}
     */
    public function build(
        Team $team,
        int $businessLocationId,
        ?int $supplierId,
        ?int $customerId,
        ?Carbon $purchaseStart,
        ?Carbon $purchaseEnd,
        Carbon $sellStart,
        Carbon $sellEnd,
    ): array {
        $sellStart = $sellStart->copy()->startOfDay();
        $sellEnd = $sellEnd->copy()->endOfDay();

        $q = SaleLine::query()
            ->join('sales', 'sales.id', '=', 'sale_lines.sale_id')
            ->join('products', 'products.id', '=', 'sale_lines.product_id')
            ->where('sales.team_id', $team->id)
            ->where('sales.status', 'final')
            ->where('sales.business_location_id', $businessLocationId)
            ->whereBetween('sales.transaction_date', [$sellStart, $sellEnd])
            ->select('sale_lines.*');

        if ($customerId) {
            $q->where('sales.customer_id', $customerId);
        }

        if ($supplierId !== null || $purchaseStart !== null || $purchaseEnd !== null) {
            $pStart = $purchaseStart?->copy()->startOfDay();
            $pEnd = $purchaseEnd?->copy()->endOfDay();

            $q->whereHas('product.purchaseLines', function ($q2) use ($team, $supplierId, $pStart, $pEnd) {
                $q2->whereHas('purchase', function ($q3) use ($team, $supplierId, $pStart, $pEnd) {
                    $q3->where('team_id', $team->id)->where('status', 'received');
                    if ($supplierId !== null) {
                        $q3->where('supplier_id', $supplierId);
                    }
                    if ($pStart && $pEnd) {
                        $q3->whereBetween('transaction_date', [$pStart, $pEnd]);
                    } elseif ($pStart) {
                        $q3->where('transaction_date', '>=', $pStart);
                    } elseif ($pEnd) {
                        $q3->where('transaction_date', '<=', $pEnd);
                    }
                });
            });
        }

        $lines = $q->with([
            'product.unit',
            'sale.customer',
            'sale.businessLocation',
        ])->orderByDesc('sales.transaction_date')->limit(1000)->get();

        $productIds = $lines->pluck('product_id')->unique()->filter()->values();
        $latestPurchaseByProduct = $this->latestPurchaseLineByProduct($team, $productIds);

        $rows = [];
        $sumPp = $sumQty = $sumSp = $sumSub = 0.0;

        foreach ($lines as $sl) {
            $p = $sl->product;
            $sale = $sl->sale;
            if (! $p || ! $sale) {
                continue;
            }

            $pl = $latestPurchaseByProduct[(int) $p->id] ?? null;
            $purchasePrice = $pl !== null
                ? (float) $pl->unit_cost_exc_tax
                : (float) ($p->single_dpp ?? 0);
            $qty = (float) $sl->quantity;
            $sellUnit = (float) $sl->unit_price_exc_tax;
            $sub = (float) $sl->line_total;

            $supplierName = '';
            $purchaseRef = '';
            $purchaseDate = '';
            $pur = null;
            if ($pl !== null) {
                $pl->loadMissing('purchase.supplier');
                $pur = $pl->purchase;
                if ($pur) {
                    $purchaseRef = (string) ($pur->ref_no ?? '#'.$pur->id);
                    $purchaseDate = $pur->transaction_date?->format('Y-m-d H:i') ?? '';
                    $sup = $pur->supplier;
                    if ($sup) {
                        $supplierName = trim(($sup->business_name ?: '').' '.implode(' ', array_filter([$sup->first_name, $sup->last_name])));
                    }
                }
            }

            $sumPp += $purchasePrice * $qty;
            $sumQty += $qty;
            $sumSp += $sellUnit * $qty;
            $sumSub += $sub;

            $rows[] = [
                'product_name' => (string) $p->name,
                'sku' => (string) ($p->sku ?? ''),
                'description' => (string) ($p->description ?? ''),
                'purchase_date' => $purchaseDate,
                'purchase_ref' => $purchaseRef,
                'purchase_url' => $pur
                    ? route('purchases.index', ['current_team' => $team->slug]).'?'.http_build_query(['search' => $purchaseRef])
                    : '',
                'lot_number' => '',
                'supplier' => $supplierName,
                'purchase_price' => $this->nf($purchasePrice),
                'sell_date' => $sale->transaction_date?->format('Y-m-d H:i') ?? '',
                'sale_ref' => (string) ($sale->invoice_no ?? '#'.$sale->id),
                'sale_url' => route('sales.detail', ['current_team' => $team->slug, 'sale' => $sale->id]),
                'customer' => $sale->customer?->display_name ?? '—',
                'location' => $sale->businessLocation?->name ?? '',
                'sell_quantity' => $this->nf($qty),
                'unit_label' => $p->unit?->short_name ?? $p->unit?->name ?? '',
                'selling_price' => $this->nf($sellUnit),
                'subtotal' => $this->nf($sub),
            ];
        }

        return [
            'rows' => $rows,
            'footer' => [
                'purchase_price' => $this->nf($sumPp),
                'sell_quantity' => $this->nf($sumQty),
                'selling_price' => $this->nf($sumSp),
                'subtotal' => $this->nf($sumSub),
            ],
        ];
    }

    /**
     * @param  Collection<int, int>  $productIds
     * @return array<int, PurchaseLine>
     */
    protected function latestPurchaseLineByProduct(Team $team, $productIds): array
    {
        if ($productIds->isEmpty()) {
            return [];
        }

        $lines = PurchaseLine::query()
            ->join('purchases', 'purchases.id', '=', 'purchase_lines.purchase_id')
            ->where('purchases.team_id', $team->id)
            ->where('purchases.status', 'received')
            ->whereIn('purchase_lines.product_id', $productIds)
            ->select('purchase_lines.*', 'purchases.transaction_date as pur_date')
            ->orderByDesc('purchases.transaction_date')
            ->orderByDesc('purchase_lines.id')
            ->with('purchase.supplier')
            ->get();

        $map = [];
        foreach ($lines as $line) {
            $pid = (int) $line->product_id;
            if (! isset($map[$pid])) {
                $map[$pid] = $line;
            }
        }

        return $map;
    }

    protected function nf(float $v): string
    {
        return number_format(round($v, 4), 4, '.', '');
    }
}
