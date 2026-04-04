<?php

namespace Tests\Feature\Customers;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_customers_index(): void
    {
        $user = User::factory()->create();
        $team = $user->fresh()->currentTeam;
        $this->assertNotNull($team);

        $this->get(route('customers.index', ['current_team' => $team->slug]))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_customers_index(): void
    {
        $user = User::factory()->create();
        $team = $user->fresh()->currentTeam;
        $this->assertNotNull($team);

        Customer::factory()->create(['team_id' => $team->id]);

        $this->actingAs($user)
            ->get(route('customers.index', ['current_team' => $team->slug]))
            ->assertOk();
    }

    public function test_authenticated_user_can_create_customer(): void
    {
        $user = User::factory()->create();
        $team = $user->fresh()->currentTeam;
        $this->assertNotNull($team);

        $this->actingAs($user)
            ->post(route('customers.store', ['current_team' => $team->slug]), [
                'party_role' => 'customer',
                'entity_type' => 'individual',
                'first_name' => 'Jane',
                'mobile' => '5551234567',
                'opening_balance' => '0',
                'assigned_to_users' => [],
                'contact_persons' => [],
            ])
            ->assertRedirect(route('customers.index', ['current_team' => $team->slug]));

        $this->assertDatabaseHas('customers', [
            'team_id' => $team->id,
            'first_name' => 'Jane',
            'mobile' => '5551234567',
        ]);
    }
}
