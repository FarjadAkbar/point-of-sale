<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleLine;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class ServiceStaffReportService
{
    /**
     * @return array{
     *   order_rows: list<array<string, mixed>>,
     *   order_footer: array{subtotal: string, total_discount: string, total_tax: string, total_amount: string},
     *   line_rows: list<array<string, mixed>>,
     *   line_footer: array{
     *     quantity: string,
     *     unit_price: string,
     *     discount: string,
     *     tax: string,
     *     net_price: string,
     *     total: string
     *   },
     * }
     */
    public function build(
        Team $team,
        Carbon $start,
        Carbon $end,
        ?int $businessLocationId,
        ?int $serviceStaffId,
    ): array {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->endOfDay();

        $orders = $this->ordersBaseQuery($team, $start, $end, $businessLocationId, $serviceStaffId)
            ->with(['createdBy:id,name', 'businessLocation:id,name'])
            ->orderByDesc('transaction_date')
            ->limit(2000)
            ->get();

        $orderRows = [];
        $subSum = 0.0;
        $discSum = 0.0;
        $taxSum = 0.0;
        $finalSum = 0.0;

        foreach ($orders as $sale) {
            $linesTotal = (float) $sale->lines_total;
            $discount = (float) $sale->discount_amount;
            $tax = (float) $sale->sale_tax_amount;
            $final = (float) $sale->final_total;
            $subSum += $linesTotal;
            $discSum += $discount;
            $taxSum += $tax;
            $finalSum += $final;

            $orderRows[] = [
                'id' => $sale->id,
                'date' => $sale->transaction_date?->toIso8601String() ?? '',
                'invoice_no' => $sale->invoice_no ?? '—',
                'service_staff' => $sale->createdBy?->name ?? '—',
                'location' => $sale->businessLocation?->name ?? '—',
                'subtotal' => $this->nf($linesTotal),
                'total_discount' => $this->nf($discount),
                'total_tax' => $this->nf($tax),
                'total_amount' => $this->nf($final),
                'sale_url' => route('sales.detail', ['current_team' => $team->slug, 'sale' => $sale->id]),
            ];
        }

        $lineRows = $this->buildLineOrderRows($team, $start, $end, $businessLocationId, $serviceStaffId);

        $qtySum = 0.0;
        $lineDiscSum = 0.0;
        $lineTaxSum = 0.0;
        $netSum = 0.0;
        $lineTotalSum = 0.0;

        foreach ($lineRows as $r) {
            $qtySum += (float) ($r['quantity_raw'] ?? 0);
            $lineDiscSum += (float) ($r['discount_raw'] ?? 0);
            $lineTaxSum += (float) ($r['tax_raw'] ?? 0);
            $netSum += (float) ($r['net_price_raw'] ?? 0);
            $lineTotalSum += (float) ($r['total_raw'] ?? 0);
        }

        return [
            'order_rows' => $orderRows,
            'order_footer' => [
                'subtotal' => $this->nf($subSum),
                'total_discount' => $this->nf($discSum),
                'total_tax' => $this->nf($taxSum),
                'total_amount' => $this->nf($finalSum),
            ],
            'line_rows' => array_map(function (array $r) {
                unset($r['quantity_raw'], $r['discount_raw'], $r['tax_raw'], $r['net_price_raw'], $r['total_raw']);

                return $r;
            }, $lineRows),
            'line_footer' => [
                'quantity' => $this->nf($qtySum),
                'unit_price' => $this->nf(0),
                'discount' => $this->nf($lineDiscSum),
                'tax' => $this->nf($lineTaxSum),
                'net_price' => $this->nf($netSum),
                'total' => $this->nf($lineTotalSum),
            ],
        ];
    }

    /**
     * @return Builder<Sale>
     */
    protected function ordersBaseQuery(
        Team $team,
        Carbon $start,
        Carbon $end,
        ?int $businessLocationId,
        ?int $serviceStaffId,
    ): Builder {
        $q = Sale::query()
            ->forTeam($team)
            ->where('status', 'final')
            ->whereBetween('transaction_date', [$start, $end]);

        if ($businessLocationId !== null) {
            $q->where('business_location_id', $businessLocationId);
        }
        if ($serviceStaffId !== null) {
            $q->where('created_by', $serviceStaffId);
        }

        return $q;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function buildLineOrderRows(
        Team $team,
        Carbon $start,
        Carbon $end,
        ?int $businessLocationId,
        ?int $serviceStaffId,
    ): array {
        $q = SaleLine::query()
            ->select('sale_lines.*')
            ->join('sales', 'sales.id', '=', 'sale_lines.sale_id')
            ->where('sales.team_id', $team->id)
            ->whereNotIn('sales.status', ['draft', 'quotation', 'final'])
            ->whereBetween('sales.transaction_date', [$start, $end]);

        if ($businessLocationId !== null) {
            $q->where('sales.business_location_id', $businessLocationId);
        }
        if ($serviceStaffId !== null) {
            $q->where('sales.created_by', $serviceStaffId);
        }

        $lines = $q
            ->with([
                'product:id,name',
                'sale' => fn ($sq) => $sq->select('id', 'invoice_no', 'transaction_date', 'created_by', 'team_id')
                    ->with(['createdBy:id,name']),
            ])
            ->orderByDesc('sales.transaction_date')
            ->orderByDesc('sale_lines.id')
            ->limit(2000)
            ->get();

        $rows = [];

        foreach ($lines as $line) {
            $sale = $line->sale;
            if (! $sale) {
                continue;
            }

            $qty = (float) $line->quantity;
            $unit = (float) $line->unit_price_exc_tax;
            $unitBefore = (float) $line->unit_price_before_discount;
            $disc = max(0.0, ($unitBefore - $unit) * $qty);
            $tax = (float) $line->line_tax_amount;
            $net = (float) $line->line_subtotal_exc_tax;
            $total = (float) $line->line_total;

            $rows[] = [
                'sale_id' => $sale->id,
                'date' => $sale->transaction_date?->toIso8601String() ?? '',
                'invoice_no' => $sale->invoice_no ?? '—',
                'service_staff' => $sale->createdBy?->name ?? '—',
                'product' => $line->product?->name ?? '—',
                'quantity' => $this->nf($qty),
                'unit_price' => $this->nf($unit),
                'discount' => $this->nf($disc),
                'tax' => $this->nf($tax),
                'net_price' => $this->nf($net),
                'total' => $this->nf($total),
                'sale_url' => route('sales.detail', ['current_team' => $team->slug, 'sale' => $sale->id]),
                'quantity_raw' => $qty,
                'discount_raw' => $disc,
                'tax_raw' => $tax,
                'net_price_raw' => $net,
                'total_raw' => $total,
            ];
        }

        return $rows;
    }

    protected function nf(float $v): string
    {
        return number_format(round($v, 4), 4, '.', '');
    }
}
