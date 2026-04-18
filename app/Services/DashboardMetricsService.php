<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class DashboardMetricsService
{
    /**
     * Financial KPIs for the team. When `$periodStart` and `$periodEnd` are null, all transaction dates are included (matches unfiltered Sales / Purchases lists).
     *
     * @return array{
     *     total_sales: float,
     *     net: float,
     *     invoice_due: float,
     *     total_sell_return: float,
     *     total_purchase: float,
     *     purchase_due: float,
     *     total_purchase_return: float,
     *     expense: float
     * }
     */
    public function financialKpis(Team $team, ?Carbon $periodStart, ?Carbon $periodEnd): array
    {
        $salesBase = Sale::query()
            ->forTeam($team)
            ->whereNotIn('status', ['draft', 'quotation']);
        $this->applyTransactionDateRange($salesBase, 'transaction_date', $periodStart, $periodEnd);

        $totalSales = (float) (clone $salesBase)->sum('final_total');

        $invoiceDue = (float) (clone $salesBase)
            ->withSum('payments', 'amount')
            ->get(['id', 'final_total', 'payments_sum_amount'])
            ->sum(function (Sale $s) {
                $paid = (float) ($s->payments_sum_amount ?? 0);

                return max(0, round((float) $s->final_total - $paid, 4));
            });

        $returnsBase = SaleReturn::query()->forTeam($team);
        $this->applyTransactionDateRange($returnsBase, 'transaction_date', $periodStart, $periodEnd);
        $totalSellReturn = (float) (clone $returnsBase)->sum('total_return');

        $net = round($totalSales - $totalSellReturn, 4);

        $purchasesBase = Purchase::query()->forTeam($team);
        $this->applyTransactionDateRange($purchasesBase, 'transaction_date', $periodStart, $periodEnd);
        $totalPurchase = (float) (clone $purchasesBase)->sum('final_total');

        $purchaseDue = (float) (clone $purchasesBase)
            ->withSum('payments', 'amount')
            ->get(['id', 'final_total', 'payments_sum_amount'])
            ->sum(function (Purchase $p) {
                $paid = (float) ($p->payments_sum_amount ?? 0);

                return max(0, round((float) $p->final_total - $paid, 4));
            });

        $expensesBase = Expense::query()->forTeam($team);
        $this->applyTransactionDateRange($expensesBase, 'transaction_date', $periodStart, $periodEnd);
        $expense = (float) (clone $expensesBase)->get(['final_total', 'is_refund'])->sum(function (Expense $e) {
            $v = (float) $e->final_total;

            return $e->is_refund ? -$v : $v;
        });

        return [
            'total_sales' => round($totalSales, 4),
            'net' => $net,
            'invoice_due' => round($invoiceDue, 4),
            'total_sell_return' => round($totalSellReturn, 4),
            'total_purchase' => round($totalPurchase, 4),
            'purchase_due' => round($purchaseDue, 4),
            'total_purchase_return' => 0.0,
            'expense' => round($expense, 4),
        ];
    }

    /**
     * Daily totals of final sales for charting (last 30 calendar days including today).
     *
     * @return list<float>
     */
    public function finalSalesLast30DaysByDay(Team $team): array
    {
        $end = Carbon::today()->endOfDay();
        $start = Carbon::today()->subDays(29)->startOfDay();

        $keys = [];
        for ($i = 0; $i < 30; $i++) {
            $keys[] = $start->copy()->addDays($i)->format('Y-m-d');
        }

        $totals = array_fill_keys($keys, 0.0);

        $sales = Sale::query()
            ->forTeam($team)
            ->where('status', 'final')
            ->whereBetween('transaction_date', [$start, $end])
            ->get(['transaction_date', 'final_total']);

        foreach ($sales as $sale) {
            if ($sale->transaction_date === null) {
                continue;
            }
            $key = $sale->transaction_date->format('Y-m-d');
            if (array_key_exists($key, $totals)) {
                $totals[$key] += (float) $sale->final_total;
            }
        }

        $out = [];
        foreach ($keys as $key) {
            $out[] = round($totals[$key], 4);
        }

        return $out;
    }

    protected function applyTransactionDateRange(
        Builder $query,
        string $column,
        ?Carbon $periodStart,
        ?Carbon $periodEnd,
    ): void {
        if ($periodStart === null || $periodEnd === null) {
            return;
        }

        $query->whereBetween($query->qualifyColumn($column), [
            $periodStart->copy()->startOfDay(),
            $periodEnd->copy()->endOfDay(),
        ]);
    }
}
