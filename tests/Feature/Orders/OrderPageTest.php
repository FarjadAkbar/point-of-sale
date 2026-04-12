<?php

namespace Tests\Feature\Orders;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OrderPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_visit_the_orders_page(): void
    {
        $user = User::factory()->create();
        $team = $user->currentTeam;

        $this->get('/'.$team->slug.'/order')->assertRedirect(route('login'));
    }

    public function test_team_members_can_visit_the_orders_page(): void
    {
        $user = User::factory()->create();
        $team = $user->currentTeam;

        $response = $this->actingAs($user)->get('/'.$team->slug.'/order');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('order/Index')
            ->has('serviceStaff')
            ->has('serviceStaffFilter')
            ->has('lineOrders')
            ->has('allOrders'));
    }

    public function test_orders_page_accepts_all_staff_query(): void
    {
        $user = User::factory()->create();
        $team = $user->currentTeam;

        $response = $this->actingAs($user)->get('/'.$team->slug.'/order?service_staff=all');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('serviceStaffFilter', 'all'));
    }
}
