<?php

namespace Database\Seeders;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use App\Services\PosRoleDefaults;
use Illuminate\Database\Seeder;

/**
 * Seeds a primary admin user (and optional demo staff) for local / staging.
 *
 * Env (all optional):
 * - SEED_ADMIN_EMAIL (default: admin@example.com)
 * - SEED_ADMIN_PASSWORD (default: password)
 * - SEED_ADMIN_NAME (default: Administrator)
 * - SEED_DEMO_USERS=true — also create cashier@ and waiter@ on the same team
 */
class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SEED_ADMIN_EMAIL', 'admin@example.com');
        $password = env('SEED_ADMIN_PASSWORD', 'password');
        $name = env('SEED_ADMIN_NAME', 'Administrator');

        $admin = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'email_verified_at' => now(),
                'is_active' => true,
            ],
        );

        if ($admin->teams()->count() === 0) {
            $team = Team::factory()->personal()->create([
                'name' => "{$admin->name}'s Team",
            ]);
            $team->members()->attach($admin->id, [
                'role' => TeamRole::Owner->value,
            ]);
            $admin->switchTeam($team);
        } else {
            $team = $admin->currentTeam ?? $admin->personalTeam();
            if ($team !== null && (int) $admin->current_team_id !== (int) $team->id) {
                $admin->switchTeam($team);
            }
        }

        $team = $admin->fresh()->currentTeam ?? $admin->personalTeam();
        if ($team === null) {
            return;
        }

        PosRoleDefaults::syncMembershipPosRoles($team);

        if (! filter_var(env('SEED_DEMO_USERS', false), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $domain = explode('@', $email)[1] ?? 'example.com';

        $cashier = User::query()->firstOrCreate(
            ['email' => "cashier@{$domain}"],
            [
                'name' => 'Demo Cashier',
                'password' => $password,
                'email_verified_at' => now(),
                'is_active' => true,
            ],
        );

        $waiter = User::query()->firstOrCreate(
            ['email' => "waiter@{$domain}"],
            [
                'name' => 'Demo Waiter',
                'password' => $password,
                'email_verified_at' => now(),
                'is_active' => true,
            ],
        );

        $cashierRole = $team->posRoles()->where('name', 'Cashier')->first();
        $waiterRole = $team->posRoles()->where('name', 'Waiter')->first();

        if (! $cashier->belongsToTeam($team)) {
            $cashier->teams()->attach($team->id, [
                'role' => TeamRole::Member->value,
                'pos_role_id' => $cashierRole?->id,
            ]);
        }

        if (! $waiter->belongsToTeam($team)) {
            $waiter->teams()->attach($team->id, [
                'role' => TeamRole::Member->value,
                'pos_role_id' => $waiterRole?->id,
            ]);
        }
    }
}
