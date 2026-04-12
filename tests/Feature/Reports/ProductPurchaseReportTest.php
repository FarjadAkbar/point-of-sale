<?php

namespace Tests\Feature\Reports;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPurchaseReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_purchase_report_page_loads_for_authenticated_member(): void
    {
        $user = User::factory()->create();
        $team = $user->fresh()->currentTeam;
        $this->assertNotNull($team);

        $this->actingAs($user)
            ->get(route('reports.product-purchase', [
                'current_team' => $team->slug,
                'start_date' => now()->startOfMonth()->toDateString(),
                'end_date' => now()->toDateString(),
            ]))
            ->assertOk();
    }
}
