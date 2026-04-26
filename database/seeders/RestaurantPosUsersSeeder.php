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

class RestaurantPosUsersSeeder extends Seeder
{
    public function run(): void
    {
        $team = Team::query()->first();

        if (! $team) {
            $team = Team::query()->create([
                'name' => 'Restaurant POS Team',
                'is_personal' => false,
            ]);
        }

        $defaultRadios = PosPermissionCatalog::defaultRadioSelections();

        $managerRole = $this->upsertPosRole(
            $team,
            'Demo - Restaurant Manager',
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
            false,
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
            false,
        );

        $waiterRole = $this->upsertPosRole(
            $team,
            'Demo - Waiter',
            [
                'dashboard.data',
                'direct_sell.access',
                'add_sale_payment',
                'customer.view_own',
            ],
            array_merge($defaultRadios, [
                'sell_view' => 'view_own_sell_only',
            ]),
            true,
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
                'unit.view',
                'warranty.view',
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
            false,
        );

        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@restaurantpos.test',
                'username' => 'restaurant_admin',
                'pos_role_id' => $managerRole->id,
                'team_role' => TeamRole::Admin,
            ],
            [
                'name' => 'Restaurant Manager',
                'email' => 'manager@restaurantpos.test',
                'username' => 'restaurant_manager',
                'pos_role_id' => $managerRole->id,
                'team_role' => TeamRole::Admin,
            ],
            [
                'name' => 'Cashier User',
                'email' => 'cashier@restaurantpos.test',
                'username' => 'restaurant_cashier',
                'pos_role_id' => $cashierRole->id,
                'team_role' => TeamRole::Member,
            ],
            [
                'name' => 'Waiter User',
                'email' => 'waiter@restaurantpos.test',
                'username' => 'restaurant_waiter',
                'pos_role_id' => $waiterRole->id,
                'team_role' => TeamRole::Member,
            ],
            [
                'name' => 'Inventory User',
                'email' => 'inventory@restaurantpos.test',
                'username' => 'restaurant_inventory',
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
        bool $isServiceStaff
    ): PosRole {
        return PosRole::query()->updateOrCreate(
            [
                'team_id' => $team->id,
                'name' => $name,
            ],
            [
                'is_service_staff' => $isServiceStaff,
                'permissions' => array_values(array_unique($permissions)),
                'radio_options' => $radioOptions,
                'is_locked' => false,
            ],
        );
    }
}
