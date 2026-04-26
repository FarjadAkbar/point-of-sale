<?php

namespace Database\Seeders;

use App\Models\PosRole;
use Illuminate\Database\Seeder;

class ReportPermissionSeeder extends Seeder
{
    public function run(): void
    {
        PosRole::query()->chunkById(100, function ($roles): void {
            foreach ($roles as $role) {
                $existing = is_array($role->permissions) ? $role->permissions : [];
                $normalized = $this->normalizeReportPermissions($existing);

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
    private function normalizeReportPermissions(array $permissions): array
    {
        $mapped = [];
        $aliasMap = [
            'report.purchase_sell' => 'purchase_n_sell_report.view',
            'reports.purchase_sell' => 'purchase_n_sell_report.view',
            'report.tax' => 'tax_report.view',
            'reports.tax' => 'tax_report.view',
            'report.contacts' => 'contacts_report.view',
            'reports.contacts' => 'contacts_report.view',
            'report.expense' => 'expense_report.view',
            'reports.expense' => 'expense_report.view',
            'report.profit_loss' => 'profit_loss_report.view',
            'reports.profit_loss' => 'profit_loss_report.view',
            'report.stock' => 'stock_report.view',
            'reports.stock' => 'stock_report.view',
            'report.trending_products' => 'trending_product_report.view',
            'reports.trending_products' => 'trending_product_report.view',
            'report.register' => 'register_report.view',
            'reports.register' => 'register_report.view',
            'report.sales_representative' => 'sales_representative.view',
            'reports.sales_representative' => 'sales_representative.view',
            'report.product_stock_value' => 'view_product_stock_value',
            'reports.product_stock_value' => 'view_product_stock_value',
            'view_purchase_sell_report' => 'purchase_n_sell_report.view',
            'view_tax_report' => 'tax_report.view',
            'view_supplier_customer_report' => 'contacts_report.view',
            'view_contacts_report' => 'contacts_report.view',
            'view_expense_report' => 'expense_report.view',
            'view_profit_loss_report' => 'profit_loss_report.view',
            'view_stock_report' => 'stock_report.view',
            'view_trending_product_report' => 'trending_product_report.view',
            'view_register_report' => 'register_report.view',
            'view_sales_representative_report' => 'sales_representative.view',
        ];

        foreach ($permissions as $permission) {
            if (! is_string($permission) || $permission === '') {
                continue;
            }

            $mapped[] = $aliasMap[$permission] ?? $permission;
        }

        return array_values(array_unique($mapped));
    }
}
