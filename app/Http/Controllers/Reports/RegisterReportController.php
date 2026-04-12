<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\RegisterReportRequest;
use App\Models\Team;
use App\Services\RegisterReportService;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class RegisterReportController extends Controller
{
    public function __construct(
        protected RegisterReportService $registerReportService,
    ) {}

    public function registerReport(RegisterReportRequest $request, Team $current_team): Response
    {
        $validated = $request->validated();
        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);
        $userId = isset($validated['register_user_id'])
            ? (int) $validated['register_user_id']
            : null;
        $status = isset($validated['register_status']) && $validated['register_status'] !== ''
            ? (string) $validated['register_status']
            : null;

        $report = $this->registerReportService->build($current_team, $start, $end, $userId, $status);

        $users = $current_team->members()
            ->orderBy('users.name')
            ->get(['users.id', 'users.name'])
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])
            ->values()
            ->all();

        return Inertia::render('reports/RegisterReport', [
            'filters' => [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'register_user_id' => $userId,
                'register_status' => $status ?? '',
            ],
            'users' => $users,
            'rows' => $report['rows'],
            'footer' => $report['footer'],
        ]);
    }
}
