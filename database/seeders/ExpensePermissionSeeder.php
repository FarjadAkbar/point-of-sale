<?php

namespace Database\Seeders;

use App\Models\PosRole;
use Illuminate\Database\Seeder;

class ExpensePermissionSeeder extends Seeder
{
    public function run(): void
    {
        PosRole::query()->chunkById(100, function ($roles): void {
            foreach ($roles as $role) {
                $existing = is_array($role->permissions) ? $role->permissions : [];
                $normalized = $this->normalizeExpensePermissions($existing);

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
    private function normalizeExpensePermissions(array $permissions): array
    {
        $mapped = [];
        $aliasMap = [
            'expenses.view' => 'all_expense.access',
            'expense.view' => 'all_expense.access',
            'expense.access' => 'all_expense.access',
            'expense.view_own' => 'view_own_expense',
            'expenses.view_own' => 'view_own_expense',
            'expense.create' => 'expense.add',
            'expense.update' => 'expense.edit',
            'expense.remove' => 'expense.delete',
            'expense_category.create' => 'expense.add',
            'expense_categories.create' => 'expense.add',
        ];

        foreach ($permissions as $permission) {
            if (! is_string($permission) || $permission === '') {
                continue;
            }

            $mapped[] = $aliasMap[$permission] ?? $permission;
        }

        if (
            in_array('expense.add', $mapped, true)
            || in_array('expense.edit', $mapped, true)
            || in_array('expense.delete', $mapped, true)
        ) {
            $mapped[] = 'view_own_expense';
        }

        return array_values(array_unique($mapped));
    }
}
