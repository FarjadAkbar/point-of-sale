<?php

namespace Database\Seeders;

use App\Models\PosRole;
use App\Support\PosPermissionCatalog;
use Illuminate\Database\Seeder;

class SellPermissionSeeder extends Seeder
{
    public function run(): void
    {
        PosRole::query()->chunkById(100, function ($roles): void {
            foreach ($roles as $role) {
                $existing = is_array($role->permissions) ? $role->permissions : [];
                $defaults = PosPermissionCatalog::defaultRadioSelections();
                $beforeRadio = array_merge($defaults, is_array($role->radio_options) ? $role->radio_options : []);
                $radio = $beforeRadio;

                $normalized = $this->normalizeSellPermissions($existing, $radio);

                if ($normalized !== $existing || $radio !== $beforeRadio) {
                    $role->update([
                        'permissions' => $normalized,
                        'radio_options' => $radio,
                    ]);
                }
            }
        });
    }

    /**
     * @param  array<int, mixed>  $permissions
     * @param  array<string, string>  $radio
     * @return array<int, string>
     */
    private function normalizeSellPermissions(array $permissions, array &$radio): array
    {
        $mapped = [];
        $aliasMap = [
            'sells.view' => 'direct_sell.view',
            'sell.view' => 'direct_sell.view',
            'sells.view_own' => 'view_own_sell_only',
            'sell.view_own' => 'view_own_sell_only',
            'sells.create' => 'direct_sell.access',
            'sell.create' => 'direct_sell.access',
            'sells.update' => 'direct_sell.update',
            'sell.update' => 'direct_sell.update',
            'sells.delete' => 'direct_sell.delete',
            'sell.delete' => 'direct_sell.delete',
            'direct_sell.create' => 'direct_sell.access',
        ];

        foreach ($permissions as $permission) {
            if (! is_string($permission) || $permission === '') {
                continue;
            }

            $normalized = $aliasMap[$permission] ?? $permission;
            $mapped[] = $normalized;

            if (in_array($normalized, ['direct_sell.update', 'direct_sell.delete'], true)) {
                $mapped[] = 'direct_sell.view';
            }
        }

        $mapped = array_values(array_unique($mapped));

        if (in_array('direct_sell.view', $mapped, true)) {
            $radio['sell_view'] = 'direct_sell.view';
        } elseif (in_array('view_own_sell_only', $mapped, true)) {
            $radio['sell_view'] = 'view_own_sell_only';
        }

        return $mapped;
    }
}
