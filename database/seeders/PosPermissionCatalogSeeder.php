<?php

namespace Database\Seeders;

use App\Support\PosPermissionCatalog;
use Illuminate\Database\Seeder;

/**
 * Permission definitions are not stored in the database: they live in
 * {@see PosPermissionCatalog} / config/pos_permission_catalog.php.
 *
 * Each team's {@see \App\Models\PosRole} rows store the selected permission keys
 * in JSON columns (checkbox list + radio_option map). Run {@see PosRoleSeeder}
 * to ensure default POS roles exist per team.
 */
class PosPermissionCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $groups = PosPermissionCatalog::groups();
        $checkboxKeys = PosPermissionCatalog::allCheckboxKeys();
        $radioDefaults = PosPermissionCatalog::defaultRadioSelections();

        $message = sprintf(
            'POS permission catalog: %d groups, %d checkbox permissions, %d radio groups (defaults).',
            count($groups),
            count($checkboxKeys),
            count($radioDefaults),
        );

        if ($this->command !== null) {
            $this->command->info($message);
        }
    }
}
