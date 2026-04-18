<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\OpenCashRegisterRequest;
use App\Models\CashRegisterSession;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class CashRegisterController extends Controller
{
    public function store(OpenCashRegisterRequest $request, Team $current_team): RedirectResponse
    {
        $userId = (int) $request->user()->id;
        $locationId = (int) $request->validated('business_location_id');
        $opening = round((float) $request->validated('amount'), 4);

        DB::transaction(function () use ($current_team, $userId, $locationId, $opening) {
            $existing = CashRegisterSession::query()
                ->forTeam($current_team)
                ->open()
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return;
            }

            CashRegisterSession::query()->create([
                'team_id' => $current_team->id,
                'business_location_id' => $locationId,
                'user_id' => $userId,
                'status' => 'open',
                'opened_at' => now(),
                'closed_at' => null,
                'opening_cash' => $opening,
            ]);
        });

        return to_route('pos.index', ['current_team' => $current_team])
            ->with('success', 'Cash register opened.');
    }

    public function close(Team $current_team): RedirectResponse
    {
        $userId = (int) request()->user()->id;

        $session = CashRegisterSession::query()
            ->forTeam($current_team)
            ->open()
            ->where('user_id', $userId)
            ->first();

        if (! $session) {
            return to_route('pos.index', ['current_team' => $current_team])
                ->with('info', 'No open register to close.');
        }

        $session->update([
            'status' => 'close',
            'closed_at' => now(),
        ]);

        return to_route('pos.index', ['current_team' => $current_team])
            ->with('success', 'Cash register closed.');
    }
}
