<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\PurchaseLine;
use App\Models\Sale;
use App\Models\SaleLine;
use App\Models\SaleReturn;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class PurchaseSellReportService
{
    /**
     * @return array<string, string>
     */
    public function build(Team $team, Carbon $start, Carbon $end, ?int $businessLocationId): array
    {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->endOfDay();

        $purchaseScope = $this->purchasesInRange($team, $start, $end, $businessLocationId);
        $saleScope = $this->salesInRange($team, $start, $end, $businessLocationId);

        $totalPurchaseExcTax = (float) PurchaseLine::query()
            ->whereHas('purchase', function (Builder $q) use ($team, $start, $end, $businessLocationId) {
                $this->applyPurchaseReportConstraints($q, $team, $start, $end, $businessLocationId);
            })
            ->sum('line_subtotal_exc_tax');

        $totalPurchaseIncTax = (float) (clone $purchaseScope)->sum('final_total');

        $purchaseDue = (float) (clone $purchaseScope)
            ->withSum('payments', 'amount')
            ->get(['id', 'final_total', 'payments_sum_amount'])
            ->sum(function (Purchase $p) {
                $paid = (float) ($p->payments_sum_amount ?? 0);

                return max(0, round((float) $p->final_total - $paid, 4));
            });

        $totalSellExcTax = (float) SaleLine::query()
            ->whereHas('sale', function (Builder $q) use ($team, $start, $end, $businessLocationId) {
                $this->applySaleReportConstraints($q, $team, $start, $end, $businessLocationId);
            })
            ->sum('line_subtotal_exc_tax');

        $totalSellIncTax = (float) (clone $saleScope)->sum('final_total');

        $saleDue = (float) (clone $saleScope)
            ->withSum('payments', 'amount')
            ->get(['id', 'final_total', 'payments_sum_amount'])
            ->sum(function (Sale $s) {
                $paid = (float) ($s->payments_sum_amount ?? 0);

                return max(0, round((float) $s->final_total - $paid, 4));
            });

        $totalPurchaseReturnIncTax = 0.0;

        $returnQuery = SaleReturn::query()->forTeam($team)->whereBetween('transaction_date', [$start, $end]);
        if ($businessLocationId) {
            $returnQuery->whereHas('parentSale', fn (Builder $q) => $q->where('business_location_id', $businessLocationId));
        }
        $totalSellReturnIncTax = (float) (clone $returnQuery)->sum('total_return');

        $sellNet = $totalSellIncTax - $totalSellReturnIncTax;
        $purchaseNet = $totalPurchaseIncTax - $totalPurchaseReturnIncTax;
        $sellMinusPurchase = $sellNet - $purchaseNet;
        $differenceDue = $saleDue - $purchaseDue;

        return [
            'total_purchase' => $this->money($totalPurchaseExcTax),
            'purchase_inc_tax' => $this->money($totalPurchaseIncTax),
            'purchase_return_inc_tax' => $this->money($totalPurchaseReturnIncTax),
            'purchase_due' => $this->money($purchaseDue),
            'total_sell' => $this->money($totalSellExcTax),
            'sell_inc_tax' => $this->money($totalSellIncTax),
            'total_sell_return' => $this->money($totalSellReturnIncTax),
            'sell_due' => $this->money($saleDue),
            'sell_minus_purchase' => $this->money($sellMinusPurchase),
            'difference_due' => $this->money($differenceDue),
        ];
    }

    protected function purchasesInRange(Team $team, Carbon $start, Carbon $end, ?int $businessLocationId): Builder
    {
        $q = Purchase::query()
            ->forTeam($team)
            ->where('status', 'received')
            ->whereBetween('transaction_date', [$start, $end]);
        if ($businessLocationId) {
            $q->where('business_location_id', $businessLocationId);
        }

        return $q;
    }

    protected function salesInRange(Team $team, Carbon $start, Carbon $end, ?int $businessLocationId): Builder
    {
        $q = Sale::query()
            ->forTeam($team)
            ->where('status', 'final')
            ->whereBetween('transaction_date', [$start, $end]);
        if ($businessLocationId) {
            $q->where('business_location_id', $businessLocationId);
        }

        return $q;
    }

    protected function applyPurchaseReportConstraints(
        Builder $q,
        Team $team,
        Carbon $start,
        Carbon $end,
        ?int $businessLocationId,
    ): void {
        $q->where('team_id', $team->id)
            ->where('status', 'received')
            ->whereBetween('transaction_date', [$start, $end]);
        if ($businessLocationId) {
            $q->where('business_location_id', $businessLocationId);
        }
    }

    protected function applySaleReportConstraints(
        Builder $q,
        Team $team,
        Carbon $start,
        Carbon $end,
        ?int $businessLocationId,
    ): void {
        $q->where('team_id', $team->id)
            ->where('status', 'final')
            ->whereBetween('transaction_date', [$start, $end]);
        if ($businessLocationId) {
            $q->where('business_location_id', $businessLocationId);
        }
    }

    protected function money(float $v): string
    {
        return number_format(round($v, 4), 4, '.', '');
    }
}
