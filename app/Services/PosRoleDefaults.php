<?php

namespace App\Services;

use App\Models\Membership;
use App\Models\PosRole;
use App\Models\Team;
use App\Support\PosPermissionCatalog;

final class PosRoleDefaults
{
    public static function ensure(Team $team): void
    {
        if ($team->posRoles()->exists()) {
            return;
        }

        $allChecks = PosPermissionCatalog::allCheckboxKeys();
        $radios = PosPermissionCatalog::defaultRadioSelections();

        PosRole::query()->create([
            'team_id' => $team->id,
            'name' => 'Admin',
            'is_service_staff' => false,
            'permissions' => $allChecks,
            'radio_options' => $radios,
            'is_locked' => true,
        ]);

        $cashierChecks = array_values(array_unique(array_filter($allChecks, fn (string $k) => str_starts_with($k, 'sell.')
            || in_array($k, ['view_cash_register', 'close_cash_register', 'product.view', 'dashboard.data', 'customer.view', 'customer.view_own'], true))));

        PosRole::query()->create([
            'team_id' => $team->id,
            'name' => 'Cashier',
            'is_service_staff' => false,
            'permissions' => $cashierChecks !== [] ? $cashierChecks : ['sell.create', 'sell.view', 'dashboard.data'],
            'radio_options' => $radios,
            'is_locked' => false,
        ]);

        PosRole::query()->create([
            'team_id' => $team->id,
            'name' => 'Waiter',
            'is_service_staff' => true,
            'permissions' => array_values(array_unique(array_merge(
                ['access_tables', 'sell.create', 'sell.view', 'dashboard.data'],
                array_filter($allChecks, fn (string $k) => str_starts_with($k, 'sell.')),
            ))),
            'radio_options' => $radios,
            'is_locked' => false,
        ]);
    }

    /**
     * Ensure default POS roles exist, then assign Admin to the team owner and
     * Cashier to any other members still missing a POS role.
     */
    public static function syncMembershipPosRoles(Team $team): void
    {
        self::ensure($team);

        $admin = $team->posRoles()->where('name', 'Admin')->first();
        $cashier = $team->posRoles()->where('name', 'Cashier')->first();
        $owner = $team->owner();

        if ($admin && $owner) {
            Membership::query()
                ->where('team_id', $team->id)
                ->where('user_id', $owner->id)
                ->update(['pos_role_id' => $admin->id]);
        }

        if (! $cashier) {
            return;
        }

        $query = Membership::query()
            ->where('team_id', $team->id)
            ->whereNull('pos_role_id');

        if ($owner) {
            $query->where('user_id', '!=', $owner->id);
        }

        $query->update(['pos_role_id' => $cashier->id]);
    }
}
