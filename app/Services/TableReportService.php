<?php

namespace App\Services;

use App\Models\RestaurantTable;
use App\Models\Team;
use Carbon\Carbon;

class TableReportService
{
    /**
     * @return array{
     *   rows: list<array{table: string, total: string, total_raw: float}>,
     *   footer: array{total: string}
     * }
     */
    public function build(Team $team, Carbon $start, Carbon $end, ?int $businessLocationId): array
    {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->endOfDay();

        $tables = RestaurantTable::query()
            ->forTeam($team)
            ->when($businessLocationId !== null, fn ($q) => $q->where('business_location_id', $businessLocationId))
            ->orderBy('name')
            ->withSum([
                'sales as period_sales_total' => function ($q) use ($team, $start, $end) {
                    $q->where('sales.team_id', $team->id)
                        ->where('sales.status', 'final')
                        ->whereBetween('sales.transaction_date', [$start, $end]);
                },
            ], 'final_total')
            ->get();

        $rows = [];
        $grand = 0.0;

        foreach ($tables as $table) {
            $raw = (float) ($table->period_sales_total ?? 0);
            $grand += $raw;
            $rows[] = [
                'table' => $table->name,
                'total' => $this->nf($raw),
                'total_raw' => $raw,
            ];
        }

        return [
            'rows' => $rows,
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
