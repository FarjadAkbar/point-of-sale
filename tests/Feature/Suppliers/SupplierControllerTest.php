<?php

namespace Tests\Feature\Suppliers;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_suppliers_index(): void
    {
        $user = User::factory()->create();
        $team = $user->fresh()->currentTeam;
        $this->assertNotNull($team);

        $this->get(route('suppliers.index', ['current_team' => $team->slug]))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_suppliers_index(): void
    {
        $user = User::factory()->create();
        $team = $user->fresh()->currentTeam;
        $this->assertNotNull($team);

        Supplier::factory()->create(['team_id' => $team->id]);

        $this->actingAs($user)
            ->get(route('suppliers.index', ['current_team' => $team->slug]))
            ->assertOk();
    }

    public function test_authenticated_user_can_create_supplier(): void
    {
        $user = User::factory()->create();
        $team = $user->fresh()->currentTeam;
        $this->assertNotNull($team);

        $this->actingAs($user)
            ->post(route('suppliers.store', ['current_team' => $team->slug]), [
                'contact_type' => 'individual',
                'first_name' => 'Jane',
                'mobile' => '5551234567',
                'opening_balance' => '0',
                'assigned_to_users' => [],
                'contact_persons' => [],
            ])
            ->assertRedirect(route('suppliers.index', ['current_team' => $team->slug]));

        $this->assertDatabaseHas('suppliers', [
            'team_id' => $team->id,
            'first_name' => 'Jane',
            'mobile' => '5551234567',
        ]);
    }
}
