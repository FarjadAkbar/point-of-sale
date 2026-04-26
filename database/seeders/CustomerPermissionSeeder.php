<?php

namespace Database\Seeders;

use App\Models\PosRole;
use App\Support\PosPermissionCatalog;
use Illuminate\Database\Seeder;

class CustomerPermissionSeeder extends Seeder
{
    public function run(): void
    {
        PosRole::query()->chunkById(100, function ($roles): void {
            foreach ($roles as $role) {
                $existing = is_array($role->permissions) ? $role->permissions : [];
                $defaults = PosPermissionCatalog::defaultRadioSelections();
                $beforeRadio = array_merge($defaults, is_array($role->radio_options) ? $role->radio_options : []);
                $radio = $beforeRadio;

                $normalized = $this->normalizeCustomerPermissions($existing, $radio);

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
    private function normalizeCustomerPermissions(array $permissions, array &$radio): array
    {
        $mapped = [];
        $aliasMap = [
            'customers.view' => 'customer.view',
            'customers.view_own' => 'customer.view_own',
            'customers.create' => 'customer.create',
            'customers.update' => 'customer.update',
            'customers.delete' => 'customer.delete',
            'customer.add' => 'customer.create',
            'customer.edit' => 'customer.update',
            'customer.remove' => 'customer.delete',
        ];

        $sellWindowPermissions = [
            'customer_with_no_sell_one_month',
            'customer_with_no_sell_three_month',
            'customer_with_no_sell_six_month',
            'customer_with_no_sell_one_year',
            'customer_irrespective_of_sell',
        ];

        foreach ($permissions as $permission) {
            if (! is_string($permission) || $permission === '') {
                continue;
            }

            $normalized = $aliasMap[$permission] ?? $permission;
            $mapped[] = $normalized;

            if (in_array($normalized, ['customer.create', 'customer.update', 'customer.delete'], true)) {
                $mapped[] = 'customer.view';
            }
        }

        $mapped = array_values(array_unique($mapped));

        if (in_array('customer.view', $mapped, true)) {
            $radio['customer_view'] = 'customer.view';
        } elseif (in_array('customer.view_own', $mapped, true)) {
            $radio['customer_view'] = 'customer.view_own';
        }

        foreach ($sellWindowPermissions as $windowPermission) {
            if (in_array($windowPermission, $mapped, true)) {
                $radio['customer_view_by_sell'] = $windowPermission;
                break;
            }
        }

        return $mapped;
    }
}
