<?php

namespace Database\Seeders;

use App\Models\PosRole;
use App\Support\PosPermissionCatalog;
use Illuminate\Database\Seeder;

class StockTransferPermissionSeeder extends Seeder
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
            'stock_transfers.view' => 'stock_transfer.view',
            'stock_transfers.view_own' => 'stock_transfer.view_own',
            'view_own_stock_transfer' => 'stock_transfer.view_own',
            'stock_transfers.create' => 'stock_transfer.create',
            'stock_transfers.update' => 'stock_transfer.update',
            'stock_transfers.delete' => 'stock_transfer.delete',
        ];

        foreach ($permissions as $permission) {
            if (! is_string($permission) || $permission === '') {
                continue;
            }

            $normalized = $aliasMap[$permission] ?? $permission;
            $mapped[] = $normalized;

            if (in_array($normalized, ['stock_transfer.create', 'stock_transfer.update', 'stock_transfer.delete'], true)) {
                $mapped[] = 'stock_transfer.view';
            }
        }

        $mapped = array_values(array_unique($mapped));

        if (in_array('stock_transfer.view', $mapped, true)) {
            $radio['stock_transfer_view'] = 'stock_transfer.view';
        } elseif (in_array('stock_transfer.view_own', $mapped, true)) {
            $radio['stock_transfer_view'] = 'stock_transfer.view_own';
        }

        return $mapped;
    }
}
