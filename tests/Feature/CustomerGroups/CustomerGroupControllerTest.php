<?php

namespace Tests\Feature\CustomerGroups;

use App\Models\CustomerGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerGroupControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_customer_groups_index(): void
    {
        $user = User::factory()->create();
        $team = $user->fresh()->currentTeam;
        $this->assertNotNull($team);

        $this->get(route('customer-groups.index', ['current_team' => $team->slug]))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_customer_groups_index(): void
    {
        $user = User::factory()->create();
        $team = $user->fresh()->currentTeam;
        $this->assertNotNull($team);

        CustomerGroup::factory()->create(['team_id' => $team->id]);

        $this->actingAs($user)
            ->get(route('customer-groups.index', ['current_team' => $team->slug]))
            ->assertOk();
    }

    public function test_authenticated_user_can_create_customer_group_with_percentage(): void
    {
        $user = User::factory()->create();
        $team = $user->fresh()->currentTeam;
        $this->assertNotNull($team);

        $this->actingAs($user)
            ->post(route('customer-groups.store', ['current_team' => $team->slug]), [
                'name' => 'Retail',
                'price_calculation_type' => 'percentage',
                'amount' => '10',
            ])
            ->assertRedirect(route('customer-groups.index', ['current_team' => $team->slug]));

        $this->assertDatabaseHas('customer_groups', [
            'team_id' => $team->id,
            'name' => 'Retail',
            'price_calculation_type' => 'percentage',
        ]);
    }
}
