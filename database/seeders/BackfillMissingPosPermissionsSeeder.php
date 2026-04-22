<?php

namespace Database\Seeders;

use App\Models\PosRole;
use App\Support\PosPermissionCatalog;
use Illuminate\Database\Seeder;

class BackfillMissingPosPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $allCheckboxes = PosPermissionCatalog::allCheckboxKeys();
        $defaultRadios = PosPermissionCatalog::defaultRadioSelections();

        PosRole::query()
            ->where('is_locked', true)
            ->where('name', 'Admin')
            ->chunkById(100, function ($roles) use ($allCheckboxes, $defaultRadios): void {
                foreach ($roles as $role) {
                    $role->update([
                        'permissions' => $allCheckboxes,
                        'radio_options' => array_merge(
                            $defaultRadios,
                            $role->radio_options ?? [],
                        ),
                    ]);
                }
            });
    }
}
