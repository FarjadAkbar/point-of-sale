<?php

namespace Database\Seeders;

use App\Models\PosRole;
use Illuminate\Database\Seeder;

class PurchasePermissionSeeder extends Seeder
{
    public function run(): void
    {
        PosRole::query()->chunkById(100, function ($roles): void {
            foreach ($roles as $role) {
                $existing = is_array($role->permissions) ? $role->permissions : [];
                $normalized = $this->normalizePurchasePermissions($existing);

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
    private function normalizePurchasePermissions(array $permissions): array
    {
        $mapped = [];
        $aliasMap = [
            'purchases.view' => 'purchase.view',
            'purchases.create' => 'purchase.create',
            'purchases.update' => 'purchase.update',
            'purchases.delete' => 'purchase.delete',
            'purchase.add' => 'purchase.create',
            'purchase.edit' => 'purchase.update',
            'purchase.remove' => 'purchase.delete',
            'purchase.view_own' => 'view_own_purchase',
            'purchases.view_own' => 'view_own_purchase',
            'purchase.payment.add' => 'purchase.payments',
            'purchase.payment.edit' => 'edit_purchase_payment',
            'purchase.payment.delete' => 'delete_purchase_payment',
            'purchase.status.update' => 'purchase.update_status',
        ];

        foreach ($permissions as $permission) {
            if (! is_string($permission) || $permission === '') {
                continue;
            }

            $normalized = $aliasMap[$permission] ?? $permission;
            $mapped[] = $normalized;

            if (in_array($normalized, [
                'purchase.create',
                'purchase.update',
                'purchase.delete',
                'purchase.payments',
                'edit_purchase_payment',
                'delete_purchase_payment',
                'purchase.update_status',
            ], true)) {
                $mapped[] = 'purchase.view';
            }
        }

        return array_values(array_unique($mapped));
    }
}
