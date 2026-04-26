<?php

namespace Database\Seeders;

use App\Models\PosRole;
use Illuminate\Database\Seeder;

class BrandUnitWarrantyPermissionSeeder extends Seeder
{
    public function run(): void
    {
        PosRole::query()->chunkById(100, function ($roles): void {
            foreach ($roles as $role) {
                $existing = is_array($role->permissions) ? $role->permissions : [];
                $normalized = $this->normalizePermissions($existing);

                if ($normalized !== $existing) {
                    $role->update(['permissions' => $normalized]);
                }
            }
        });
    }

    /**
     * @param  array<int, mixed>  $permissions
     * @return array<int, string>
     */
    private function normalizePermissions(array $permissions): array
    {
        $mapped = [];
        $aliasMap = [
            'brands.view' => 'brand.view',
            'brands.create' => 'brand.create',
            'brands.update' => 'brand.update',
            'brands.delete' => 'brand.delete',
            'units.view' => 'unit.view',
            'units.create' => 'unit.create',
            'units.update' => 'unit.update',
            'units.delete' => 'unit.delete',
            'warranties.view' => 'warranty.view',
            'warranties.create' => 'warranty.create',
            'warranties.update' => 'warranty.update',
            'warranties.delete' => 'warranty.delete',
        ];
        $viewPermissionForModule = [
            'brand' => 'brand.view',
            'unit' => 'unit.view',
            'warranty' => 'warranty.view',
        ];

        foreach ($permissions as $permission) {
            if (! is_string($permission) || $permission === '') {
                continue;
            }

            $normalized = $aliasMap[$permission] ?? $permission;
            $mapped[] = $normalized;

            [$module, $action] = array_pad(explode('.', $normalized, 2), 2, null);
            if (
                is_string($module) &&
                is_string($action) &&
                in_array($action, ['create', 'update', 'delete'], true) &&
                isset($viewPermissionForModule[$module])
            ) {
                $mapped[] = $viewPermissionForModule[$module];
            }
        }

        return array_values(array_unique($mapped));
    }
}
