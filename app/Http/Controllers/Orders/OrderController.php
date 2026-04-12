<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Team;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request, Team $current_team): Response
    {
        $team = $current_team;
        $members = $team->members()
            ->orderBy('name')
            ->get(['users.id', 'users.name']);

        $rawStaff = $request->query('service_staff');
        $filterCreatedBy = match (true) {
            $rawStaff === 'all' || $rawStaff === '' => null,
            $rawStaff === null => $request->user()->id,
            default => (int) $rawStaff,
        };

        if ($filterCreatedBy !== null && ! $members->contains('id', $filterCreatedBy)) {
            abort(403);
        }

        $baseQuery = static fn () => Sale::query()
            ->forTeam($team)
            ->with([
                'customer:id,display_name',
                'businessLocation:id,name',
                'createdBy:id,name',
            ])
            ->whereNotIn('status', ['draft', 'quotation']);

        $lineOrdersQuery = $baseQuery()
            ->where('status', '!=', 'final')
            ->orderByDesc('transaction_date')
            ->limit(100);

        $allOrdersQuery = $baseQuery()
            ->where('status', 'final')
            ->orderByDesc('transaction_date')
            ->limit(100);

        if ($filterCreatedBy !== null) {
            $lineOrdersQuery->where('created_by', $filterCreatedBy);
            $allOrdersQuery->where('created_by', $filterCreatedBy);
        }

        $map = static fn (Sale $sale) => [
            'id' => $sale->id,
            'invoice_no' => $sale->invoice_no,
            'transaction_date' => $sale->transaction_date?->toIso8601String(),
            'status' => $sale->status,
            'customer' => $sale->customer ? [
                'display_name' => $sale->customer->display_name,
            ] : null,
            'business_location' => $sale->businessLocation ? [
                'name' => $sale->businessLocation->name,
            ] : null,
            'created_by_name' => $sale->createdBy?->name,
        ];

        return Inertia::render('order/Index', [
            'serviceStaff' => $members->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
            ]),
            'serviceStaffFilter' => $filterCreatedBy === null
                ? 'all'
                : (string) $filterCreatedBy,
            'lineOrders' => $lineOrdersQuery->get()->map($map),
            'allOrders' => $allOrdersQuery->get()->map($map),
        ]);
    }
}
