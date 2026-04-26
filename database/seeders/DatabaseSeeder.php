<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            BrandUnitWarrantyPermissionSeeder::class,
            ProductPermissionSeeder::class,
            SettingsPermissionSeeder::class,
            BookingPermissionSeeder::class,
            PurchasePermissionSeeder::class,
            CustomerPermissionSeeder::class,
            SupplierRoleUserPermissionSeeder::class,
            StockAdjustmentPermissionSeeder::class,
            StockTransferPermissionSeeder::class,
            SellPermissionSeeder::class,
            DraftPermissionSeeder::class,
            QuotationPermissionSeeder::class,
            ShipmentPermissionSeeder::class,
            CashRegisterPermissionSeeder::class,
            BrandPermissionSeeder::class,
            TaxRatePermissionSeeder::class,
            ReportPermissionSeeder::class,
            ExpensePermissionSeeder::class,
            RestaurantPosUsersSeeder::class,
        ]);
    }
}
