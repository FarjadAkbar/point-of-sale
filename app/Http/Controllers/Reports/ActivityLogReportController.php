<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\ActivityLogReportRequest;
use App\Models\Team;
use App\Services\ActivityLogReportService;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogReportController extends Controller
{
    public function __construct(
        protected ActivityLogReportService $activityLogReportService,
    ) {}

    public function activityLog(ActivityLogReportRequest $request, Team $current_team): Response
    {
        $validated = $request->validated();
        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);
        $userId = isset($validated['user_id']) ? (int) $validated['user_id'] : null;
        $subjectType = isset($validated['subject_type']) && $validated['subject_type'] !== ''
            ? (string) $validated['subject_type']
            : null;

        $rows = $this->activityLogReportService->build($current_team, $start, $end, $userId, $subjectType);

        $users = $current_team->members()
            ->orderBy('users.name')
            ->get(['users.id', 'users.name'])
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])
            ->values()
            ->all();

        $subjectTypes = [
            ['value' => '', 'label' => 'All'],
            ['value' => 'contact', 'label' => 'Contact'],
            ['value' => 'user', 'label' => 'User'],
            ['value' => 'sell', 'label' => 'Sell'],
            ['value' => 'purchase', 'label' => 'Purchase'],
            ['value' => 'sales_order', 'label' => 'Sales Order'],
            ['value' => 'purchase_order', 'label' => 'Purchase Order'],
            ['value' => 'sell_return', 'label' => 'Sell Return'],
            ['value' => 'purchase_return', 'label' => 'Purchase Return'],
            ['value' => 'sell_transfer', 'label' => 'Stock Transfer'],
            ['value' => 'stock_adjustment', 'label' => 'Stock Adjustment'],
            ['value' => 'expense', 'label' => 'Expense'],
        ];

        return Inertia::render('reports/ActivityLog', [
            'filters' => [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'user_id' => $userId,
                'subject_type' => $subjectType ?? '',
            ],
            'users' => $users,
            'subjectTypes' => $subjectTypes,
            'rows' => $rows,
        ]);
    }
}
