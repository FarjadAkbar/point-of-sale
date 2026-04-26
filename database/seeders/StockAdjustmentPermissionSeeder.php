<?php

namespace Database\Seeders;

use App\Models\PosRole;
use App\Support\PosPermissionCatalog;
use Illuminate\Database\Seeder;

class StockAdjustmentPermissionSeeder extends Seeder
{
    public function run(): void
    {
        PosRole::query()->chunkById(100, function ($roles): void {
            foreach ($roles as $role) {
                $existing = is_array($role->permissions) ? $role->permissions : [];
                $defaults = PosPermissionCatalog::defaultRadioSelections();
                $beforeRadio = array_merge($defaults, is_array($role->radio_options) ? $role->radio_options : []);
                $radio = $beforeRadio;

                $normalized = $this->normalizePermissions($existing, $radio);

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
    private function normalizePermissions(array $permissions, array &$radio): array
    {
        $mapped = [];
        $aliasMap = [
            'stock_adjustments.view' => 'stock_adjustment.view',
            'stock_adjustments.view_own' => 'view_own_stock_adjustment',
            'stock_adjustments.create' => 'stock_adjustment.create',
            'stock_adjustments.update' => 'stock_adjustment.update',
            'stock_adjustments.delete' => 'stock_adjustment.delete',
        ];

        foreach ($permissions as $permission) {
            if (! is_string($permission) || $permission === '') {
                continue;
            }

            $normalized = $aliasMap[$permission] ?? $permission;
            $mapped[] = $normalized;

            if (in_array($normalized, ['stock_adjustment.create', 'stock_adjustment.update', 'stock_adjustment.delete'], true)) {
                $mapped[] = 'stock_adjustment.view';
            }
        }

        $mapped = array_values(array_unique($mapped));

        if (in_array('stock_adjustment.view', $mapped, true)) {
            $radio['stock_adjustment_view'] = 'stock_adjustment.view';
        } elseif (in_array('view_own_stock_adjustment', $mapped, true)) {
            $radio['stock_adjustment_view'] = 'view_own_stock_adjustment';
        }

        return $mapped;
    }
}
