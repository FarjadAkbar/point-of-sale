<?php

namespace App\Services;

use App\Models\ProductStock;
use App\Models\SaleLine;
use App\Models\StockAdjustmentLine;
use App\Models\StockTransferLine;
use App\Models\Team;

class StockReportService
{
    /**
     * @return array{
     *     rows: list<array<string, mixed>>,
     *     summary: array<string, string>,
     *     footer: array<string, string>,
     * }
     */
    public function build(
        Team $team,
        ?int $businessLocationId,
        ?int $categoryId,
        ?int $subcategoryId,
        ?int $brandId,
        ?int $unitId,
    ): array {
        $soldMap = $this->soldByProductLocation($team, $businessLocationId);
        $transferMap = $this->transferActivityByProductLocation($team, $businessLocationId);
        $adjustMap = $this->adjustmentQtyByProductLocation($team, $businessLocationId);

        $q = ProductStock::query()
            ->whereHas('product', function ($q) use ($team, $categoryId, $subcategoryId, $brandId, $unitId, $businessLocationId) {
                $q->forTeam($team)->where('manage_stock', true);
                if ($brandId) {
                    $q->where('brand_id', $brandId);
                }
                if ($unitId) {
                    $q->where('unit_id', $unitId);
                }
                if ($categoryId) {
                    $q->where('category_id', $categoryId);
                }
                if ($subcategoryId) {
                    $q->where('subcategory_id', $subcategoryId);
                }
                if ($businessLocationId) {
                    $q->forBusinessLocation($businessLocationId);
                }
            })
            ->with(['product.unit', 'product.category', 'product.brand', 'businessLocation'])
            ->when($businessLocationId, fn ($q) => $q->where('business_location_id', $businessLocationId));

        $rows = [];
        foreach ($q->get() as $ps) {
            $p = $ps->product;
            if (! $p || $p->team_id !== $team->id) {
                continue;
            }

            $locId = (int) $ps->business_location_id;
            $pid = (int) $p->id;
            $key = $pid.'_'.$locId;

            $qty = (float) $ps->quantity;
            $dpp = (float) ($p->single_dpp ?? 0);
            $dsp = (float) ($p->single_dsp ?? 0);
            $valPp = round($qty * $dpp, 4);
            $valSp = round($qty * $dsp, 4);
            $pot = round($valSp - $valPp, 4);

            $sold = $soldMap[$key] ?? 0.0;
            $xfer = $transferMap[$key] ?? 0.0;
            $adj = $adjustMap[$key] ?? 0.0;

            $rows[] = [
                'product_id' => $pid,
                'product_edit_url' => route('products.edit', [
                    'current_team' => $team->slug,
                    'product' => $pid,
                ]),
                'sku' => (string) ($p->sku ?? ''),
                'product_name' => (string) $p->name,
                'variation' => '',
                'category' => $p->category?->name ?? '',
                'location' => $ps->businessLocation?->name ?? '',
                'unit_selling_price' => $this->nf($dsp),
                'current_stock' => $this->nf($qty),
                'unit_short_name' => $p->unit?->short_name ?? $p->unit?->name ?? '',
                'stock_value_pp' => $this->nf($valPp),
                'stock_value_sp' => $this->nf($valSp),
                'potential_profit' => $this->nf($pot),
                'total_sold' => $this->nf($sold),
                'total_transferred' => $this->nf($xfer),
                'total_adjusted' => $this->nf($adj),
            ];
        }

        usort($rows, fn ($a, $b) => strcmp((string) $a['sku'], (string) $b['sku']));

        $summary = $this->summarize($rows);
        $footer = $this->footerTotals($rows);

        return [
            'rows' => $rows,
            'summary' => $summary,
            'footer' => $footer,
        ];
    }

    /**
     * @return array<string, float>
     */
    protected function soldByProductLocation(Team $team, ?int $onlyLocationId): array
    {
        $q = SaleLine::query()
            ->join('sales', 'sales.id', '=', 'sale_lines.sale_id')
            ->where('sales.team_id', $team->id)
            ->where('sales.status', 'final')
            ->selectRaw('sale_lines.product_id as pid, sales.business_location_id as lid, SUM(sale_lines.quantity) as qty')
            ->groupBy('sale_lines.product_id', 'sales.business_location_id');

        if ($onlyLocationId) {
            $q->where('sales.business_location_id', $onlyLocationId);
        }

        $map = [];
        foreach ($q->get() as $r) {
            $map[(int) $r->pid.'_'.(int) $r->lid] = (float) $r->qty;
        }

        return $map;
    }

    /**
     * @return array<string, float>
     */
    protected function transferActivityByProductLocation(Team $team, ?int $onlyLocationId): array
    {
        $q = StockTransferLine::query()
            ->join('stock_transfers as t', 't.id', '=', 'stock_transfer_lines.stock_transfer_id')
            ->where('t.team_id', $team->id)
            ->where('t.status', 'completed')
            ->select([
                'stock_transfer_lines.product_id as pid',
                'stock_transfer_lines.quantity as qty',
                't.from_business_location_id as from_id',
                't.to_business_location_id as to_id',
            ]);

        $map = [];
        foreach ($q->get() as $r) {
            $qty = abs((float) $r->qty);
            foreach ([(int) $r->from_id, (int) $r->to_id] as $lid) {
                if ($onlyLocationId !== null && $lid !== $onlyLocationId) {
                    continue;
                }
                $k = (int) $r->pid.'_'.$lid;
                $map[$k] = round(($map[$k] ?? 0) + $qty, 4);
            }
        }

        return $map;
    }

    /**
     * @return array<string, float>
     */
    protected function adjustmentQtyByProductLocation(Team $team, ?int $onlyLocationId): array
    {
        $q = StockAdjustmentLine::query()
            ->join('stock_adjustments as a', 'a.id', '=', 'stock_adjustment_lines.stock_adjustment_id')
            ->where('a.team_id', $team->id)
            ->selectRaw('stock_adjustment_lines.product_id as pid, a.business_location_id as lid, SUM(ABS(stock_adjustment_lines.quantity)) as qty')
            ->groupBy('stock_adjustment_lines.product_id', 'a.business_location_id');

        if ($onlyLocationId) {
            $q->where('a.business_location_id', $onlyLocationId);
        }

        $map = [];
        foreach ($q->get() as $r) {
            $map[(int) $r->pid.'_'.(int) $r->lid] = (float) $r->qty;
        }

        return $map;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, string>
     */
    protected function summarize(array $rows): array
    {
        $pp = $sp = $pot = 0.0;
        foreach ($rows as $r) {
            $pp += floatval((string) $r['stock_value_pp']);
            $sp += floatval((string) $r['stock_value_sp']);
            $pot += floatval((string) $r['potential_profit']);
        }
        $margin = $sp > 0.00001 ? round(100.0 * $pot / $sp, 2) : 0.0;

        return [
            'closing_stock_by_pp' => $this->nf($pp),
            'closing_stock_by_sp' => $this->nf($sp),
            'potential_profit' => $this->nf($pot),
            'profit_margin_percent' => $this->nf($margin),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, string>
     */
    protected function footerTotals(array $rows): array
    {
        $stock = $vpp = $vsp = $pot = $sold = $xfer = $adj = 0.0;
        foreach ($rows as $r) {
            $stock += floatval((string) $r['current_stock']);
            $vpp += floatval((string) $r['stock_value_pp']);
            $vsp += floatval((string) $r['stock_value_sp']);
            $pot += floatval((string) $r['potential_profit']);
            $sold += floatval((string) $r['total_sold']);
            $xfer += floatval((string) $r['total_transferred']);
            $adj += floatval((string) $r['total_adjusted']);
        }

        return [
            'current_stock' => $this->nf($stock),
            'stock_value_pp' => $this->nf($vpp),
            'stock_value_sp' => $this->nf($vsp),
            'potential_profit' => $this->nf($pot),
            'total_sold' => $this->nf($sold),
            'total_transferred' => $this->nf($xfer),
            'total_adjusted' => $this->nf($adj),
        ];
    }

    protected function nf(float $v): string
    {
        return number_format(round($v, 4), 4, '.', '');
    }
}
