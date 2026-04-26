<?php

namespace Database\Seeders;

use App\Models\PosRole;
use Illuminate\Database\Seeder;

class ProductPermissionSeeder extends Seeder
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
            'products.view' => 'product.view',
            'products.create' => 'product.create',
            'products.update' => 'product.update',
            'products.delete' => 'product.delete',
            'product.add' => 'product.create',
            'product.edit' => 'product.update',
            'product.remove' => 'product.delete',
            'product.opening_stock.add' => 'product.opening_stock',
            'product.view_purchase_cost' => 'view_purchase_price',
        ];

        foreach ($permissions as $permission) {
            if (! is_string($permission) || $permission === '') {
                continue;
            }

            $normalized = $aliasMap[$permission] ?? $permission;
            $mapped[] = $normalized;

            if (in_array($normalized, ['product.create', 'product.update', 'product.delete'], true)) {
                $mapped[] = 'product.view';
            }
        }

        return array_values(array_unique($mapped));
    }
}
