<?php

namespace Database\Seeders;

use App\Models\PosRole;
use Illuminate\Database\Seeder;

class TaxRatePermissionSeeder extends Seeder
{
    public function run(): void
    {
        PosRole::query()->chunkById(100, function ($roles): void {
            foreach ($roles as $role) {
                $existing = is_array($role->permissions) ? $role->permissions : [];
                $normalized = $this->normalizeTaxRatePermissions($existing);

                if ($normalized !== $existing) {
                    $role->update([
                        'permissions' => $normalized,
                    ]);
                }
            }
        });
    }

    /**
     * @param  array<int, mixed>  $permissions
     * @return array<int, string>
     */
    private function normalizeTaxRatePermissions(array $permissions): array
    {
        $mapped = [];
        $aliasMap = [
            'taxes.view' => 'tax_rate.view',
            'taxes.create' => 'tax_rate.create',
            'taxes.update' => 'tax_rate.update',
            'taxes.delete' => 'tax_rate.delete',
            'tax.view' => 'tax_rate.view',
            'tax.create' => 'tax_rate.create',
            'tax.update' => 'tax_rate.update',
            'tax.delete' => 'tax_rate.delete',
            'view_tax_rate' => 'tax_rate.view',
            'add_tax_rate' => 'tax_rate.create',
            'edit_tax_rate' => 'tax_rate.update',
            'delete_tax_rate' => 'tax_rate.delete',
        ];

        foreach ($permissions as $permission) {
            if (! is_string($permission) || $permission === '') {
                continue;
            }

            $normalized = $aliasMap[$permission] ?? $permission;
            $mapped[] = $normalized;

            if (in_array($normalized, ['tax_rate.create', 'tax_rate.update', 'tax_rate.delete'], true)) {
                $mapped[] = 'tax_rate.view';
            }
        }

        return array_values(array_unique($mapped));
    }
}
