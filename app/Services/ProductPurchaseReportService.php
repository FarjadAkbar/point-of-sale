<?php

namespace App\Services;

use App\Models\PurchaseLine;
use App\Models\Supplier;
use App\Models\Team;
use Carbon\Carbon;

class ProductPurchaseReportService
{
    /**
     * @return array{rows: list<array<string, mixed>>, footer: array<string, string>}
     */
    public function build(
        Team $team,
        Carbon $start,
        Carbon $end,
        ?int $businessLocationId,
        ?int $supplierId,
        ?int $brandId,
        ?int $productId,
        ?string $searchProduct,
    ): array {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->endOfDay();

        if ($businessLocationId === null) {
            return [
                'rows' => [],
                'footer' => $this->emptyFooter(),
            ];
        }

        $q = PurchaseLine::query()
            ->join('purchases', 'purchases.id', '=', 'purchase_lines.purchase_id')
            ->join('products', 'products.id', '=', 'purchase_lines.product_id')
            ->where('purchases.team_id', $team->id)
            ->where('products.team_id', $team->id)
            ->where('purchases.status', 'received')
            ->whereBetween('purchases.transaction_date', [$start, $end])
            ->where('purchases.business_location_id', $businessLocationId)
            ->select('purchase_lines.*');

        if ($supplierId !== null) {
            $q->where('purchases.supplier_id', $supplierId);
        }
        if ($brandId !== null) {
            $q->where('products.brand_id', $brandId);
        }
        if ($productId !== null) {
            $q->where('products.id', $productId);
        }
        if ($searchProduct !== null && $searchProduct !== '') {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $searchProduct).'%';
            $q->where(function ($q2) use ($term) {
                $q2->where('products.name', 'like', $term)
                    ->orWhere('products.sku', 'like', $term);
            });
        }

        $lines = $q->with(['product.unit', 'purchase.supplier', 'purchase.businessLocation'])
            ->orderByDesc('purchases.transaction_date')
            ->orderByDesc('purchases.id')
            ->limit(2000)
            ->get();

        $rows = [];
        $sumQty = 0.0;
        $sumAdj = 0.0;
        $sumSub = 0.0;

        foreach ($lines as $line) {
            $p = $line->product;
            $pur = $line->purchase;
            if (! $p || ! $pur) {
                continue;
            }

            $qty = (float) $line->quantity;
            $unitPrice = (float) $line->unit_cost_exc_tax;
            $sub = (float) $line->line_total;
            $adj = 0.0;

            $sumQty += $qty;
            $sumAdj += $adj;
            $sumSub += $sub;

            $ref = (string) ($pur->ref_no ?? '#'.$pur->id);
            $rows[] = [
                'purchase_id' => $pur->id,
                'purchase_line_id' => $line->id,
                'product_name' => (string) $p->name,
                'sku' => (string) ($p->sku ?? ''),
                'supplier' => $this->formatSupplier($pur->supplier),
                'reference_no' => $ref,
                'date' => $pur->transaction_date?->toDateString() ?? '',
                'quantity' => $this->nf($qty),
                'quantity_adjusted' => $this->nf($adj),
                'unit_label' => $p->unit?->short_name ?? $p->unit?->name ?? '',
                'unit_price' => $this->nf($unitPrice),
                'subtotal' => $this->nf($sub),
            ];
        }

        return [
            'rows' => $rows,
            'footer' => [
                'quantity' => $this->nf($sumQty),
                'quantity_adjusted' => $this->nf($sumAdj),
                'subtotal' => $this->nf($sumSub),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function emptyFooter(): array
    {
        return [
            'quantity' => $this->nf(0),
            'quantity_adjusted' => $this->nf(0),
            'subtotal' => $this->nf(0),
        ];
    }

    protected function formatSupplier(?Supplier $s): string
    {
        if ($s === null) {
            return '';
        }
        $person = trim(implode(' ', array_filter([$s->first_name, $s->last_name])));
        $biz = $s->business_name ? trim((string) $s->business_name) : '';
        if ($biz !== '' && $person !== '') {
            return $biz."\n".$person;
        }
        if ($biz !== '') {
            return $biz;
        }
        if ($person !== '') {
            return $person;
        }

        return $s->display_name;
    }

    protected function nf(float $v): string
    {
        return number_format(round($v, 4), 4, '.', '');
    }
}
