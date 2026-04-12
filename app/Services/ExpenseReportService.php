<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Team;
use Carbon\Carbon;

class ExpenseReportService
{
    /**
     * @return array{
     *   chart: list<array{label: string, total: string, total_raw: float}>,
     *   rows: list<array{category: string, total: string}>,
     *   footer: array{total: string}
     * }
     */
    public function build(Team $team, Carbon $start, Carbon $end, ?int $businessLocationId, ?int $categoryId): array
    {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->endOfDay();

        $q = Expense::query()
            ->forTeam($team)
            ->whereBetween('transaction_date', [$start, $end]);

        if ($businessLocationId !== null) {
            $q->where('business_location_id', $businessLocationId);
        }
        if ($categoryId !== null) {
            $q->where('expense_category_id', $categoryId);
        }

        $aggregates = (clone $q)
            ->selectRaw('expense_category_id')
            ->selectRaw('SUM(CASE WHEN is_refund = 1 THEN -final_total ELSE final_total END) as total')
            ->groupBy('expense_category_id')
            ->get();

        $categoryNames = ExpenseCategory::query()
            ->forTeam($team)
            ->whereIn('id', $aggregates->pluck('expense_category_id')->filter())
            ->pluck('name', 'id');

        $rows = [];
        foreach ($aggregates as $row) {
            $cid = $row->expense_category_id;
            $label = $cid === null ? 'Uncategorized' : (string) ($categoryNames[$cid] ?? 'Category #'.$cid);
            $total = (float) $row->total;
            $rows[] = [
                'category' => $label,
                'total' => $this->nf($total),
                'total_raw' => $total,
                '_sort' => $total,
            ];
        }

        usort($rows, fn (array $a, array $b) => $b['_sort'] <=> $a['_sort']);

        $grand = (float) (clone $q)->selectRaw(
            'SUM(CASE WHEN is_refund = 1 THEN -final_total ELSE final_total END) as t',
        )->value('t');

        $chart = [];
        foreach ($rows as $r) {
            $chart[] = [
                'label' => $r['category'],
                'total' => $r['total'],
                'total_raw' => $r['total_raw'],
            ];
        }

        $tableRows = [];
        foreach ($rows as $r) {
            $tableRows[] = [
                'category' => $r['category'],
                'total' => $r['total'],
            ];
        }

        return [
            'chart' => $chart,
            'rows' => $tableRows,
            'footer' => [
                'total' => $this->nf($grand),
            ],
        ];
    }

    protected function nf(float $v): string
    {
        return number_format(round($v, 4), 4, '.', '');
    }
}
