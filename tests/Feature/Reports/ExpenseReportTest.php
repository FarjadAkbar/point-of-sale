<?php

namespace Tests\Feature\Reports;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_report_page_loads_for_authenticated_member(): void
    {
        $user = User::factory()->create();
        $team = $user->fresh()->currentTeam;
        $this->assertNotNull($team);

        $this->actingAs($user)
            ->get(route('reports.expense', [
                'current_team' => $team->slug,
                'start_date' => now()->startOfMonth()->toDateString(),
                'end_date' => now()->toDateString(),
            ]))
            ->assertOk();
    }
}
