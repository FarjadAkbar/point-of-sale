<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Team;
use App\Services\DashboardMetricsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardMetricsService $metrics,
    ) {}

    public function __invoke(Request $request, Team $current_team): Response
    {
        $teamId = $current_team->id;
        $now = now();

        $importStats = [
            'products' => [
                'total' => Product::query()->where('team_id', $teamId)->count(),
                'last_7_days' => Product::query()
                    ->where('team_id', $teamId)
                    ->where('created_at', '>=', $now->copy()->subDays(7))
                    ->count(),
                'last_30_days' => Product::query()
                    ->where('team_id', $teamId)
                    ->where('created_at', '>=', $now->copy()->subDays(30))
                    ->count(),
            ],
            'customers' => [
                'last_30_days' => Customer::query()
                    ->where('team_id', $teamId)
                    ->where('created_at', '>=', $now->copy()->subDays(30))
                    ->count(),
            ],
            'suppliers' => [
                'last_30_days' => Supplier::query()
                    ->where('team_id', $teamId)
                    ->where('created_at', '>=', $now->copy()->subDays(30))
                    ->count(),
            ],
        ];

        $periodKey = $this->validatedPeriod($request->query('period'));
        [$periodStart, $periodEnd] = $this->periodBounds($periodKey);

        $financialKpis = $this->metrics->financialKpis($current_team, $periodStart, $periodEnd);

        return Inertia::render('Dashboard', [
            'importStats' => $importStats,
            'financialKpis' => $financialKpis,
            'dashboardPeriod' => $periodKey,
            'salesTrend' => $this->metrics->finalSalesLast30DaysByDay($current_team),
        ]);
    }

    /**
     * @return array{0: Carbon|null, 1: Carbon|null}
     */
    protected function periodBounds(string $periodKey): array
    {
        $today = Carbon::today();

        return match ($periodKey) {
            'today' => [$today->copy()->startOfDay(), $today->copy()->endOfDay()],
            'week' => [
                Carbon::now()->copy()->startOfWeek(),
                Carbon::now()->copy()->endOfWeek(),
            ],
            'month' => [
                Carbon::now()->copy()->startOfMonth(),
                Carbon::now()->copy()->endOfMonth(),
            ],
            'last_30' => [
                $today->copy()->subDays(29)->startOfDay(),
                $today->copy()->endOfDay(),
            ],
            default => [null, null],
        };
    }

    protected function validatedPeriod(mixed $period): string
    {
        $key = is_string($period) ? $period : 'all';

        return in_array($key, ['all', 'today', 'week', 'month', 'last_30'], true) ? $key : 'all';
    }
}
