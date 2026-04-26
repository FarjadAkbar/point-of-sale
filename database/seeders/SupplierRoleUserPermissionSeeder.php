<?php

namespace Database\Seeders;

use App\Models\PosRole;
use App\Support\PosPermissionCatalog;
use Illuminate\Database\Seeder;

class SupplierRoleUserPermissionSeeder extends Seeder
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
            'suppliers.view' => 'supplier.view',
            'suppliers.view_own' => 'supplier.view_own',
            'suppliers.create' => 'supplier.create',
            'suppliers.update' => 'supplier.update',
            'suppliers.delete' => 'supplier.delete',
            'supplier.add' => 'supplier.create',
            'supplier.edit' => 'supplier.update',
            'supplier.remove' => 'supplier.delete',
            'roles.add' => 'roles.create',
            'roles.edit' => 'roles.update',
            'role.view' => 'roles.view',
            'role.create' => 'roles.create',
            'role.update' => 'roles.update',
            'role.delete' => 'roles.delete',
            'users.view' => 'user.view',
            'users.create' => 'user.create',
            'users.update' => 'user.update',
            'users.delete' => 'user.delete',
        ];

        foreach ($permissions as $permission) {
            if (! is_string($permission) || $permission === '') {
                continue;
            }

            $normalized = $aliasMap[$permission] ?? $permission;
            $mapped[] = $normalized;

            if (in_array($normalized, ['supplier.create', 'supplier.update', 'supplier.delete'], true)) {
                $mapped[] = 'supplier.view';
            }
        }

        $mapped = array_values(array_unique($mapped));

        if (in_array('supplier.view', $mapped, true)) {
            $radio['supplier_view'] = 'supplier.view';
        } elseif (in_array('supplier.view_own', $mapped, true)) {
            $radio['supplier_view'] = 'supplier.view_own';
        }

        return $mapped;
    }
}
