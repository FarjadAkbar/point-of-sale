<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\SaleLine;
use App\Models\Team;
use Carbon\Carbon;

class ProductSellReportService
{
    /**
     * @return array{rows: list<array<string, mixed>>, footer: array<string, string>}
     */
    public function build(
        Team $team,
        Carbon $start,
        Carbon $end,
        ?int $businessLocationId,
        ?int $customerId,
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

        $q = SaleLine::query()
            ->join('sales', 'sales.id', '=', 'sale_lines.sale_id')
            ->join('products', 'products.id', '=', 'sale_lines.product_id')
            ->where('sales.team_id', $team->id)
            ->where('products.team_id', $team->id)
            ->where('sales.status', 'final')
            ->whereBetween('sales.transaction_date', [$start, $end])
            ->where('sales.business_location_id', $businessLocationId)
            ->select('sale_lines.*');

        if ($customerId !== null) {
            $q->where('sales.customer_id', $customerId);
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

        $lines = $q->with(['product.unit', 'sale.customer', 'sale.businessLocation'])
            ->orderByDesc('sales.transaction_date')
            ->orderByDesc('sales.id')
            ->limit(2000)
            ->get();

        $rows = [];
        $sumQty = 0.0;
        $sumAdj = 0.0;
        $sumSub = 0.0;

        foreach ($lines as $line) {
            $p = $line->product;
            $sale = $line->sale;
            if (! $p || ! $sale) {
                continue;
            }

            $qty = (float) $line->quantity;
            $unitPrice = (float) $line->unit_price_exc_tax;
            $sub = (float) $line->line_total;
            $adj = 0.0;

            $sumQty += $qty;
            $sumAdj += $adj;
            $sumSub += $sub;

            $ref = (string) ($sale->invoice_no ?? '#'.$sale->id);
            $rows[] = [
                'product_name' => (string) $p->name,
                'sku' => (string) ($p->sku ?? ''),
                'customer' => $sale->customer?->display_name ?? '—',
                'reference_no' => $ref,
                'sale_url' => route('sales.detail', ['current_team' => $team->slug, 'sale' => $sale->id]),
                'date' => $sale->transaction_date?->toDateString() ?? '',
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

    protected function nf(float $v): string
    {
        return number_format(round($v, 4), 4, '.', '');
    }
}
