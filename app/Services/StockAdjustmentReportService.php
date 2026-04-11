<?php

namespace App\Services;

use App\Models\StockAdjustment;
use App\Models\Team;
use Carbon\Carbon;

class StockAdjustmentReportService
{
    /**
     * @return array{
     *     totals: array<string, string>,
     *     rows: list<array<string, mixed>>,
     * }
     */
    public function build(Team $team, Carbon $start, Carbon $end, ?int $businessLocationId): array
    {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->endOfDay();

        $q = StockAdjustment::query()
            ->forTeam($team)
            ->whereBetween('transaction_date', [$start, $end])
            ->with(['businessLocation', 'creator'])
            ->when($businessLocationId, fn ($q) => $q->where('business_location_id', $businessLocationId))
            ->orderByDesc('transaction_date')
            ->limit(500);

        $normal = $abnormal = $total = $recovered = 0.0;
        $rows = [];

        foreach ($q->get() as $a) {
            $ft = (float) $a->final_total;
            $rec = (float) $a->total_amount_recovered;
            $total += $ft;
            $recovered += $rec;
            if ($a->adjustment_type === 'normal') {
                $normal += $ft;
            } else {
                $abnormal += $ft;
            }

            $rows[] = [
                'id' => $a->id,
                'detail_url' => route('stock-adjustments.index', ['current_team' => $team->slug]),
                'transaction_date' => $a->transaction_date?->toIso8601String(),
                'ref_no' => $a->ref_no ?? '',
                'location' => $a->businessLocation?->name ?? '',
                'adjustment_type' => $a->adjustment_type,
                'final_total' => $this->nf($ft),
                'total_amount_recovered' => $this->nf($rec),
                'reason' => $a->additional_notes ?? '',
                'added_by' => $a->creator?->name ?? '—',
            ];
        }

        return [
            'totals' => [
                'total_normal' => $this->nf($normal),
                'total_abnormal' => $this->nf($abnormal),
                'total_amount' => $this->nf($total),
                'total_recovered' => $this->nf($recovered),
            ],
            'rows' => $rows,
        ];
    }

    protected function nf(float $v): string
    {
        return number_format(round($v, 4), 4, '.', '');
    }
}
