<?php

namespace App\Services;

use App\Models\SaleActivity;
use App\Models\Team;
use Carbon\Carbon;

class ActivityLogReportService
{
    /**
     * Subject types that currently have backing data (sale activity log).
     */
    public const SUPPORTED_SUBJECT_TYPES = ['sell'];

    /**
     * @return list<array{
     *   id: int,
     *   date: string,
     *   subject_type: string,
     *   action: string,
     *   by: string,
     *   note: string
     * }>
     */
    public function build(Team $team, Carbon $start, Carbon $end, ?int $userId, ?string $subjectType): array
    {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->endOfDay();

        $subjectType = $subjectType === '' ? null : $subjectType;

        if ($subjectType !== null && ! in_array($subjectType, self::SUPPORTED_SUBJECT_TYPES, true)) {
            return [];
        }

        $query = SaleActivity::query()
            ->join('sales', 'sales.id', '=', 'sale_activities.sale_id')
            ->where('sales.team_id', $team->id)
            ->whereBetween('sale_activities.created_at', [$start, $end])
            ->select('sale_activities.*')
            ->orderByDesc('sale_activities.created_at');

        if ($userId !== null) {
            $query->where('sale_activities.user_id', $userId);
        }

        $activities = $query->with(['user:id,name'])->get();

        $rows = [];

        foreach ($activities as $activity) {
            $rows[] = [
                'id' => (int) $activity->id,
                'date' => $activity->created_at?->toIso8601String() ?? '',
                'subject_type' => 'Sell',
                'action' => $activity->action,
                'by' => $activity->user?->name ?? '—',
                'note' => $activity->note ?? '',
            ];
        }

        return $rows;
    }
}
