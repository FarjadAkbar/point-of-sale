<?php

namespace Database\Seeders;

use App\Models\PosRole;
use App\Support\PosPermissionCatalog;
use Illuminate\Database\Seeder;

class BrandPermissionSeeder extends Seeder
{
    public function run(): void
    {
        PosRole::query()->chunkById(100, function ($roles): void {
            foreach ($roles as $role) {
                $existing = is_array($role->permissions) ? $role->permissions : [];
                $defaults = PosPermissionCatalog::defaultRadioSelections();
                $beforeRadio = array_merge($defaults, is_array($role->radio_options) ? $role->radio_options : []);
                $radio = $beforeRadio;

                $normalized = $this->normalizeBrandPermissions($existing, $radio);

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
    private function normalizeBrandPermissions(array $permissions, array &$radio): array
    {
        $mapped = [];
        $aliasMap = [
            'brands.view' => 'brand.view',
            'brands.view_own' => 'brand.view_own',
            'brands.create' => 'brand.create',
            'brands.update' => 'brand.update',
            'brands.delete' => 'brand.delete',
            'brand.view_all' => 'brand.view',
            'brand.viewown' => 'brand.view_own',
            'view_brand' => 'brand.view',
            'add_brand' => 'brand.create',
            'edit_brand' => 'brand.update',
            'delete_brand' => 'brand.delete',
        ];

        foreach ($permissions as $permission) {
            if (! is_string($permission) || $permission === '') {
                continue;
            }

            $normalized = $aliasMap[$permission] ?? $permission;
            $mapped[] = $normalized;

            if (in_array($normalized, ['brand.create', 'brand.update', 'brand.delete'], true)) {
                $mapped[] = 'brand.view';
            }
        }

        $mapped = array_values(array_unique($mapped));

        if (in_array('brand.view', $mapped, true)) {
            $radio['brand_view'] = 'brand.view';
        } elseif (in_array('brand.view_own', $mapped, true)) {
            $radio['brand_view'] = 'brand.view_own';
        }

        return $mapped;
    }
}
