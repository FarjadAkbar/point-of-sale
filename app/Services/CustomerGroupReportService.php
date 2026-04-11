<?php

namespace App\Services;

use App\Models\CustomerGroup;
use App\Models\Sale;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CustomerGroupReportService
{
    /**
     * @return list<array{group_id: int|null, group_name: string, total_sale: string}>
     */
    public function build(
        Team $team,
        Carbon $start,
        Carbon $end,
        ?int $businessLocationId,
        ?int $customerGroupId,
    ): array {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->endOfDay();

        $q = Sale::query()
            ->forTeam($team)
            ->where('status', 'final')
            ->whereBetween('transaction_date', [$start, $end])
            ->join('customers', 'customers.id', '=', 'sales.customer_id')
            ->whereIn('customers.party_role', ['customer', 'both']);

        if ($businessLocationId) {
            $q->where('sales.business_location_id', $businessLocationId);
        }

        if ($customerGroupId) {
            $q->where('customers.customer_group_id', $customerGroupId);
        }

        $rows = $q
            ->select([
                'customers.customer_group_id as group_id',
                DB::raw('SUM(sales.final_total) as total_sale'),
            ])
            ->groupBy('customers.customer_group_id')
            ->orderBy('group_id')
            ->get();

        $groupNames = CustomerGroup::query()
            ->forTeam($team)
            ->whereIn('id', $rows->pluck('group_id')->filter()->unique())
            ->pluck('name', 'id');

        $out = [];
        foreach ($rows as $row) {
            $gid = $row->group_id !== null ? (int) $row->group_id : null;
            $name = $gid === null
                ? 'Ungrouped'
                : (string) ($groupNames[$gid] ?? 'Unknown group');

            $out[] = [
                'group_id' => $gid,
                'group_name' => $name,
                'total_sale' => number_format((float) $row->total_sale, 4, '.', ''),
            ];
        }

        usort($out, fn ($a, $b) => strcmp((string) $a['group_name'], (string) $b['group_name']));

        return $out;
    }
}
