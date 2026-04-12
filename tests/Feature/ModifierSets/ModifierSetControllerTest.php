<?php

namespace Tests\Feature\ModifierSets;

use App\Models\ModifierSet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModifierSetControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_modifier_sets_index(): void
    {
        $user = User::factory()->create();
        $team = $user->fresh()->currentTeam;
        $this->assertNotNull($team);

        $this->get(route('settings.modifiers.index', ['current_team' => $team->slug]))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_modifier_sets_index(): void
    {
        $user = User::factory()->create();
        $team = $user->fresh()->currentTeam;
        $this->assertNotNull($team);

        $this->actingAs($user)
            ->get(route('settings.modifiers.index', ['current_team' => $team->slug]))
            ->assertOk();
    }

    public function test_authenticated_user_can_create_modifier_set(): void
    {
        $user = User::factory()->create();
        $team = $user->fresh()->currentTeam;
        $this->assertNotNull($team);

        $this->actingAs($user)
            ->post(route('settings.modifiers.store', ['current_team' => $team->slug]), [
                'name' => 'Sauces',
                'items' => [
                    ['name' => 'Extra hot', 'price' => '1.50'],
                    ['name' => 'Mild', 'price' => '0'],
                ],
            ])
            ->assertRedirect(route('settings.modifiers.index', ['current_team' => $team->slug]));

        $set = ModifierSet::query()->where('team_id', $team->id)->where('name', 'Sauces')->first();
        $this->assertNotNull($set);
        $this->assertCount(2, $set->items);
        $this->assertSame('Extra hot', $set->items[0]->name);
        $this->assertEquals('1.5000', $set->items[0]->price);
    }
}
