<?php

namespace Tests\Feature\Reports;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TableReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_table_report_page_loads_for_authenticated_member(): void
    {
        $user = User::factory()->create();
        $team = $user->fresh()->currentTeam;
        $this->assertNotNull($team);

        $this->actingAs($user)
            ->get(route('reports.table-report', [
                'current_team' => $team->slug,
                'start_date' => now()->startOfMonth()->toDateString(),
                'end_date' => now()->toDateString(),
            ]))
            ->assertOk();
    }
}
