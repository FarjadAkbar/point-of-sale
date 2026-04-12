<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class SalesRepresentativeReportService
{
    /**
     * @return array{
     *   summary: array{
     *     total_sales: string,
     *     total_sales_return: string,
     *     total_sales_final: string,
     *     total_expenses: string,
     *   },
     *   sales_rows: list<array<string, mixed>>,
     *   sales_footer: array<string, mixed>,
     *   commission_rows: list<array<string, mixed>>,
     *   commission_footer: array<string, mixed>,
     *   expense_rows: list<array<string, mixed>>,
     *   expense_footer: array<string, mixed>,
     * }
     */
    public function build(
        Team $team,
        Carbon $start,
        Carbon $end,
        ?int $businessLocationId,
        ?int $userId,
    ): array {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->endOfDay();

        $salesTotal = (float) $this->salesBaseQuery($team, $start, $end, $businessLocationId, $userId, false)
            ->sum('final_total');

        $returnsTotal = (float) $this->returnsInRange($team, $start, $end, $businessLocationId, $userId)
            ->sum('total_return');

        $expensesTotal = (float) $this->expensesBaseQuery($team, $start, $end, $businessLocationId, $userId)
            ->selectRaw('SUM(CASE WHEN is_refund = 1 THEN -final_total ELSE final_total END) as t')
            ->value('t');

        $salesCollection = $this->salesBaseQuery($team, $start, $end, $businessLocationId, $userId, false)
            ->with(['customer', 'businessLocation'])
            ->withSum('payments as payments_sum_amount', 'amount')
            ->orderByDesc('transaction_date')
            ->limit(2000)
            ->get();

        $commissionCollection = $this->salesBaseQuery($team, $start, $end, $businessLocationId, $userId, true)
            ->with(['customer', 'businessLocation'])
            ->withSum('payments as payments_sum_amount', 'amount')
            ->orderByDesc('transaction_date')
            ->limit(2000)
            ->get();

        $expenseCollection = $this->expensesBaseQuery($team, $start, $end, $businessLocationId, $userId)
            ->with(['expenseCategory', 'businessLocation', 'expenseForUser', 'contact'])
            ->withSum('payments as payments_sum_amount', 'amount')
            ->orderByDesc('transaction_date')
            ->limit(2000)
            ->get();

        return [
            'summary' => [
                'total_sales' => $this->nf($salesTotal),
                'total_sales_return' => $this->nf($returnsTotal),
                'total_sales_final' => $this->nf($salesTotal - $returnsTotal),
                'total_expenses' => $this->nf($expensesTotal),
            ],
            'sales_rows' => $salesCollection->map(fn (Sale $s) => $this->mapSaleRow($team, $s))->all(),
            'sales_footer' => $this->saleTableFooter($salesCollection),
            'commission_rows' => $commissionCollection->map(fn (Sale $s) => $this->mapSaleRow($team, $s))->all(),
            'commission_footer' => $this->saleTableFooter($commissionCollection),
            'expense_rows' => $expenseCollection->map(fn (Expense $e) => $this->mapExpenseRow($e))->all(),
            'expense_footer' => $this->expenseTableFooter($expenseCollection),
        ];
    }

    /**
     * @return Builder<Sale>
     */
    protected function salesBaseQuery(
        Team $team,
        Carbon $start,
        Carbon $end,
        ?int $businessLocationId,
        ?int $userId,
        bool $commissionOnly,
    ): Builder {
        $q = Sale::query()
            ->forTeam($team)
            ->where('status', 'final')
            ->whereBetween('transaction_date', [$start, $end]);

        if ($businessLocationId !== null) {
            $q->where('business_location_id', $businessLocationId);
        }
        if ($userId !== null) {
            $q->where('created_by', $userId);
        }
        if ($commissionOnly) {
            $q->whereNotNull('sales_commission_agent_id');
        }

        return $q;
    }

    /**
     * @return Builder<SaleReturn>
     */
    protected function returnsInRange(
        Team $team,
        Carbon $start,
        Carbon $end,
        ?int $businessLocationId,
        ?int $userId,
    ): Builder {
        $q = SaleReturn::query()
            ->forTeam($team)
            ->whereBetween('transaction_date', [$start, $end])
            ->whereHas('parentSale', function (Builder $q2) use ($team, $businessLocationId, $userId) {
                $q2->where('team_id', $team->id)->where('status', 'final');
                if ($businessLocationId !== null) {
                    $q2->where('business_location_id', $businessLocationId);
                }
                if ($userId !== null) {
                    $q2->where('created_by', $userId);
                }
            });

        return $q;
    }

    /**
     * @return Builder<Expense>
     */
    protected function expensesBaseQuery(
        Team $team,
        Carbon $start,
        Carbon $end,
        ?int $businessLocationId,
        ?int $userId,
    ): Builder {
        $q = Expense::query()
            ->forTeam($team)
            ->whereBetween('transaction_date', [$start, $end]);

        if ($businessLocationId !== null) {
            $q->where('business_location_id', $businessLocationId);
        }
        if ($userId !== null) {
            $q->where(function (Builder $q2) use ($userId) {
                $q2->where('created_by', $userId)
                    ->orWhere('expense_for_user_id', $userId);
            });
        }

        return $q;
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapSaleRow(Team $team, Sale $s): array
    {
        $final = (float) $s->final_total;
        $paid = (float) ($s->payments_sum_amount ?? 0);
        $remaining = max(0, round($final - $paid, 4));
        $status = $remaining <= 0.0001 ? 'paid' : 'due';

        return [
            'date' => $s->transaction_date?->toIso8601String() ?? '',
            'invoice_no' => $s->invoice_no ?? '—',
            'customer_name' => $s->customer?->display_name ?? '—',
            'location' => $s->businessLocation?->name ?? '—',
            'payment_status' => $status,
            'payment_status_label' => $status === 'paid' ? 'Paid' : 'Due',
            'total_amount' => $this->nf($final),
            'total_paid' => $this->nf($paid),
            'total_remaining' => $this->nf($remaining),
            'sale_url' => route('sales.detail', ['current_team' => $team->slug, 'sale' => $s->id]),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Sale>  $sales
     * @return array<string, mixed>
     */
    protected function saleTableFooter($sales): array
    {
        $paidCount = 0;
        $dueCount = 0;
        $sumFinal = 0.0;
        $sumPaid = 0.0;
        $sumRemaining = 0.0;

        foreach ($sales as $s) {
            $final = (float) $s->final_total;
            $paid = (float) ($s->payments_sum_amount ?? 0);
            $remaining = max(0, round($final - $paid, 4));
            $sumFinal += $final;
            $sumPaid += $paid;
            $sumRemaining += $remaining;
            if ($remaining <= 0.0001) {
                $paidCount++;
            } else {
                $dueCount++;
            }
        }

        return [
            'payment_status_html' => "Paid - {$paidCount}\nDue - {$dueCount}",
            'total_amount' => $this->nf($sumFinal),
            'total_paid' => $this->nf($sumPaid),
            'remaining_html' => 'Sell Due - '.$this->nf($sumRemaining)."\n".'Sell Return Due - '.$this->nf(0.0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapExpenseRow(Expense $e): array
    {
        $final = (float) $e->final_total;
        $signed = $e->is_refund ? -$final : $final;
        $paid = (float) ($e->payments_sum_amount ?? 0);
        $target = $e->is_refund ? abs($final) : $final;
        $status = $paid + 0.0001 >= $target && $target > 0 ? 'paid' : ($target <= 0 ? 'paid' : 'due');

        $for = '—';
        if ($e->expenseForUser) {
            $for = $e->expenseForUser->name;
        } elseif ($e->contact) {
            $for = $e->contact->display_name;
        }

        return [
            'date' => $e->transaction_date?->toIso8601String() ?? '',
            'ref_no' => $e->ref_no ?? '—',
            'category' => $e->expenseCategory?->name ?? 'Uncategorized',
            'location' => $e->businessLocation?->name ?? '—',
            'payment_status' => $status,
            'payment_status_label' => $status === 'paid' ? 'Paid' : 'Due',
            'total_amount' => $this->nf($signed),
            'expense_for' => $for,
            'note' => $e->additional_notes ?? '',
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Expense>  $expenses
     * @return array<string, mixed>
     */
    protected function expenseTableFooter($expenses): array
    {
        $sum = 0.0;
        foreach ($expenses as $e) {
            $final = (float) $e->final_total;
            $sum += $e->is_refund ? -$final : $final;
        }

        return [
            'payment_status_html' => '',
            'total_amount' => $this->nf($sum),
        ];
    }

    protected function nf(float $v): string
    {
        return number_format(round($v, 4), 4, '.', '');
    }
}
