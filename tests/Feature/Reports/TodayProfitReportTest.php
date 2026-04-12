<?php

namespace Tests\Feature\Reports;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodayProfitReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_today_profit_json_returns_summary_for_team_member(): void
    {
        $user = User::factory()->create();
        $team = $user->fresh()->currentTeam;
        $this->assertNotNull($team);

        $this->actingAs($user)
            ->getJson(route('reports.today-profit', ['current_team' => $team->slug]))
            ->assertOk()
            ->assertJsonStructure([
                'date',
                'summary' => [
                    'gross_profit',
                    'net_profit',
                    'cogs',
                ],
            ]);
    }
}
