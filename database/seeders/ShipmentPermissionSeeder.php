<?php

namespace Database\Seeders;

use App\Models\PosRole;
use App\Support\PosPermissionCatalog;
use Illuminate\Database\Seeder;

class ShipmentPermissionSeeder extends Seeder
{
    public function run(): void
    {
        PosRole::query()->chunkById(100, function ($roles): void {
            foreach ($roles as $role) {
                $existing = is_array($role->permissions) ? $role->permissions : [];
                $defaults = PosPermissionCatalog::defaultRadioSelections();
                $beforeRadio = array_merge($defaults, is_array($role->radio_options) ? $role->radio_options : []);
                $radio = $beforeRadio;

                $normalized = $this->normalizeShipmentPermissions($existing, $radio);

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
    private function normalizeShipmentPermissions(array $permissions, array &$radio): array
    {
        $mapped = [];
        $aliasMap = [
            'shipments.access_all' => 'access_shipping',
            'shipment.access_all' => 'access_shipping',
            'shipments.access_own' => 'access_own_shipping',
            'shipment.access_own' => 'access_own_shipping',
            'shipments.pending_only' => 'access_pending_shipments_only',
            'shipment.pending_only' => 'access_pending_shipments_only',
            'shipments.commission_agent_own' => 'access_commission_agent_shipping',
            'shipment.commission_agent_own' => 'access_commission_agent_shipping',
            'access_all_shipments' => 'access_shipping',
            'access_own_shipments' => 'access_own_shipping',
        ];

        foreach ($permissions as $permission) {
            if (! is_string($permission) || $permission === '') {
                continue;
            }

            $normalized = $aliasMap[$permission] ?? $permission;
            $mapped[] = $normalized;
        }

        $mapped = array_values(array_unique($mapped));

        if (in_array('access_shipping', $mapped, true)) {
            $radio['shipping_view'] = 'access_shipping';
        } elseif (in_array('access_own_shipping', $mapped, true)) {
            $radio['shipping_view'] = 'access_own_shipping';
        }

        return $mapped;
    }
}
