<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Services\PosRoleDefaults;
use Illuminate\Database\Seeder;

/**
 * Creates default POS roles (Admin, Cashier, Waiter) for every team when missing,
 * and assigns POS roles on memberships (owner → Admin; others without a role → Cashier).
 */
class PosRoleSeeder extends Seeder
{
    public function run(): void
    {
        Team::query()->orderBy('id')->each(function (Team $team): void {
            PosRoleDefaults::syncMembershipPosRoles($team);
        });
    }
}
