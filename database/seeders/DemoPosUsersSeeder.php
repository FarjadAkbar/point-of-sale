<?php

namespace Database\Seeders;

use App\Enums\TeamRole;
use App\Models\Membership;
use App\Models\PosRole;
use App\Models\Team;
use App\Models\User;
use App\Support\PosPermissionCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoPosUsersSeeder extends Seeder
{
    public function run(): void
    {
        $team = Team::query()->first();

        if (! $team) {
            $team = Team::query()->create([
                'name' => 'Demo POS Team',
                'is_personal' => false,
            ]);
        }

        $defaultRadios = PosPermissionCatalog::defaultRadioSelections();

        $adminRole = $this->upsertPosRole(
            $team,
            'Demo - Admin',
            PosPermissionCatalog::allCheckboxKeys(),
            $defaultRadios,
        );

        $managerRole = $this->upsertPosRole(
            $team,
            'Demo - Manager',
            [
                'dashboard.data',
                'direct_sell.access',
                'direct_sell.view',
                'direct_sell.update',
                'purchase.view',
                'purchase.create',
                'product.view',
                'product.create',
                'product.update',
                'brand.view',
                'brand.create',
                'brand.update',
                'brand.delete',
                'unit.view',
                'unit.create',
                'unit.update',
                'unit.delete',
                'warranty.view',
                'warranty.create',
                'warranty.update',
                'warranty.delete',
                'customer.view',
                'supplier.view',
                'all_expense.access',
                'expense.add',
                'expense.edit',
                'purchase_n_sell_report.view',
                'tax_report.view',
                'contacts_report.view',
                'expense_report.view',
                'profit_loss_report.view',
                'stock_report.view',
                'register_report.view',
                'sales_representative.view',
                'view_product_stock_value',
                'view_cash_register',
                'close_cash_register',
            ],
            $defaultRadios,
        );

        $cashierRole = $this->upsertPosRole(
            $team,
            'Demo - Cashier',
            [
                'dashboard.data',
                'direct_sell.access',
                'direct_sell.view',
                'direct_sell.update',
                'add_sale_payment',
                'edit_sale_payment',
                'delete_sale_payment',
                'customer.view',
                'view_cash_register',
                'close_cash_register',
            ],
            $defaultRadios,
        );

        $inventoryRole = $this->upsertPosRole(
            $team,
            'Demo - Inventory Supervisor',
            [
                'dashboard.data',
                'product.view',
                'product.create',
                'product.update',
                'product.opening_stock',
                'view_purchase_price',
                'brand.view',
                'brand.create',
                'brand.update',
                'brand.delete',
                'unit.view',
                'unit.create',
                'unit.update',
                'unit.delete',
                'warranty.view',
                'warranty.create',
                'warranty.update',
                'warranty.delete',
                'purchase.view',
                'purchase.create',
                'purchase.update',
                'stock_adjustment.view',
                'stock_adjustment.create',
                'stock_transfer.view',
                'stock_transfer.create',
                'stock_report.view',
                'view_product_stock_value',
            ],
            $defaultRadios,
        );

        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@demopos.test',
                'username' => 'demo_admin',
                'pos_role_id' => $adminRole->id,
                'team_role' => TeamRole::Admin,
            ],
            [
                'name' => 'Manager User',
                'email' => 'manager@demopos.test',
                'username' => 'demo_manager',
                'pos_role_id' => $managerRole->id,
                'team_role' => TeamRole::Admin,
            ],
            [
                'name' => 'Cashier User',
                'email' => 'cashier@demopos.test',
                'username' => 'demo_cashier',
                'pos_role_id' => $cashierRole->id,
                'team_role' => TeamRole::Member,
            ],
            [
                'name' => 'Inventory User',
                'email' => 'inventory@demopos.test',
                'username' => 'demo_inventory',
                'pos_role_id' => $inventoryRole->id,
                'team_role' => TeamRole::Member,
            ],
        ];

        foreach ($users as $definition) {
            $user = User::query()->updateOrCreate(
                ['email' => $definition['email']],
                [
                    'name' => $definition['name'],
                    'username' => $definition['username'],
                    'password' => Hash::make('password'),
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'current_team_id' => $team->id,
                ],
            );

            Membership::query()->updateOrCreate(
                [
                    'team_id' => $team->id,
                    'user_id' => $user->id,
                ],
                [
                    'role' => $definition['team_role']->value,
                    'pos_role_id' => $definition['pos_role_id'],
                ],
            );
        }
    }

    /**
     * @param  array<int, string>  $permissions
     * @param  array<string, string>  $radioOptions
     */
    private function upsertPosRole(
        Team $team,
        string $name,
        array $permissions,
        array $radioOptions,
    ): PosRole {
        return PosRole::query()->updateOrCreate(
            [
                'team_id' => $team->id,
                'name' => $name,
            ],
            [
                'permissions' => array_values(array_unique($permissions)),
                'radio_options' => $radioOptions,
                'is_locked' => false,
            ],
        );
    }
}
