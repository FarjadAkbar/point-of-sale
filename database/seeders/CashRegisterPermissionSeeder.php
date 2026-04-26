<?php

namespace Database\Seeders;

use App\Models\PosRole;
use Illuminate\Database\Seeder;

class CashRegisterPermissionSeeder extends Seeder
{
    public function run(): void
    {
        PosRole::query()->chunkById(100, function ($roles): void {
            foreach ($roles as $role) {
                $existing = is_array($role->permissions) ? $role->permissions : [];
                $normalized = $this->normalizeCashRegisterPermissions($existing);

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
    private function normalizeCashRegisterPermissions(array $permissions): array
    {
        $mapped = [];
        $aliasMap = [
            'cash_register.view' => 'view_cash_register',
            'cashregister.view' => 'view_cash_register',
            'cash_register.close' => 'close_cash_register',
            'cashregister.close' => 'close_cash_register',
        ];

        foreach ($permissions as $permission) {
            if (! is_string($permission) || $permission === '') {
                continue;
            }

            $normalized = $aliasMap[$permission] ?? $permission;
            $mapped[] = $normalized;
        }

        return array_values(array_unique($mapped));
    }
}
