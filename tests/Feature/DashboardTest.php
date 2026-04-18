<?php

namespace Tests\Feature;

use App\Models\BusinessLocation;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        User::factory()->create();

        $response = $this->get(route('dashboard', ['current_team' => 'missing-slug']));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $team = $user->currentTeam;
        $this->assertNotNull($team);

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard', ['current_team' => $team->slug]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('importStats')
            ->has('importStats.products', fn (Assert $p) => $p
                ->where('total', 0)
                ->where('last_7_days', 0)
                ->where('last_30_days', 0)
            )
            ->has('importStats.customers', fn (Assert $c) => $c
                ->where('last_30_days', 0)
            )
            ->has('importStats.suppliers', fn (Assert $s) => $s
                ->where('last_30_days', 0)
            )
            ->where('dashboardPeriod', 'all')
            ->has('financialKpis', fn (Assert $f) => $f
                ->where('total_sales', 0)
                ->where('net', 0)
                ->where('invoice_due', 0)
                ->where('total_sell_return', 0)
                ->where('total_purchase', 0)
                ->where('purchase_due', 0)
                ->where('total_purchase_return', 0)
                ->where('expense', 0)
            )
            ->has('salesTrend', 30)
        );
    }

    public function test_total_sales_matches_database_scope_for_sales_list()
    {
        $user = User::factory()->create();
        $team = $user->currentTeam;
        $this->assertNotNull($team);

        $location = BusinessLocation::query()->create([
            'team_id' => $team->id,
            'name' => 'Main',
        ]);

        $customer = Customer::factory()->create(['team_id' => $team->id]);

        Sale::query()->create([
            'team_id' => $team->id,
            'customer_id' => $customer->id,
            'business_location_id' => $location->id,
            'invoice_no' => 'INV-1',
            'transaction_date' => now(),
            'status' => 'final',
            'final_total' => 100,
            'lines_total' => 100,
        ]);

        Sale::query()->create([
            'team_id' => $team->id,
            'customer_id' => $customer->id,
            'business_location_id' => $location->id,
            'invoice_no' => 'INV-2',
            'transaction_date' => now(),
            'status' => 'proforma',
            'final_total' => 50,
            'lines_total' => 50,
        ]);

        Sale::query()->create([
            'team_id' => $team->id,
            'customer_id' => $customer->id,
            'business_location_id' => $location->id,
            'invoice_no' => null,
            'transaction_date' => now(),
            'status' => 'draft',
            'final_total' => 999,
            'lines_total' => 999,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard', ['current_team' => $team->slug]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('financialKpis.total_sales', 150)
        );
    }
}
