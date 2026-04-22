<?php

use App\Http\Controllers\Bookings\BookingController;
use App\Http\Controllers\Brands\BrandController;
use App\Http\Controllers\BusinessLocations\BusinessLocationController;
use App\Http\Controllers\CustomerGroups\CustomerGroupController;
use App\Http\Controllers\Customers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Expenses\ExpenseCategoryController;
use App\Http\Controllers\Expenses\ExpenseController;
use App\Http\Controllers\Kitchen\KitchenController;
use App\Http\Controllers\ModifierSets\ModifierSetController;
use App\Http\Controllers\Orders\OrderController;
use App\Http\Controllers\PaymentAccounts\AccountTypeController;
use App\Http\Controllers\PaymentAccounts\PaymentAccountController;
use App\Http\Controllers\Pos\CashRegisterController;
use App\Http\Controllers\Pos\PosController;
use App\Http\Controllers\PosRoles\PosRoleController;
use App\Http\Controllers\ProductCategories\ProductCategoryController;
use App\Http\Controllers\Products\ProductController;
use App\Http\Controllers\Purchases\PurchaseController;
use App\Http\Controllers\Purchases\PurchaseReturnController;
use App\Http\Controllers\Reports\ActivityLogReportController;
use App\Http\Controllers\Reports\CustomerGroupReportController;
use App\Http\Controllers\Reports\CustomerSuppliersReportController;
use App\Http\Controllers\Reports\ExpenseReportController;
use App\Http\Controllers\Reports\ItemsReportController;
use App\Http\Controllers\Reports\ProductPurchaseReportController;
use App\Http\Controllers\Reports\ProfitLossController;
use App\Http\Controllers\Reports\PurchasePaymentReportController;
use App\Http\Controllers\Reports\PurchaseSellController;
use App\Http\Controllers\Reports\RegisterReportController;
use App\Http\Controllers\Reports\SalesRepresentativeReportController;
use App\Http\Controllers\Reports\SellPaymentReportController;
use App\Http\Controllers\Reports\ServiceStaffReportController;
use App\Http\Controllers\Reports\StockAdjustmentReportController;
use App\Http\Controllers\Reports\StockReportController;
use App\Http\Controllers\Reports\TableReportController;
use App\Http\Controllers\Reports\TaxReportController;
use App\Http\Controllers\Reports\TrendingProductsReportController;
use App\Http\Controllers\Sales\DiscountController;
use App\Http\Controllers\Sales\SaleController;
use App\Http\Controllers\Sales\SaleReturnController;
use App\Http\Controllers\Sales\ShipmentController;
use App\Http\Controllers\SalesCommissionAgents\SalesCommissionAgentController;
use App\Http\Controllers\SellingPriceGroups\SellingPriceGroupController;
use App\Http\Controllers\Settings\BarcodeSettingsController;
use App\Http\Controllers\Settings\PaymentSettingsController;
use App\Http\Controllers\Settings\ReceiptPrinterController;
use App\Http\Controllers\StockAdjustments\StockAdjustmentController;
use App\Http\Controllers\StockTransfers\StockTransferController;
use App\Http\Controllers\Suppliers\SupplierController;
use App\Http\Controllers\Tables\RestaurantTableController;
use App\Http\Controllers\Taxes\TaxesController;
use App\Http\Controllers\Taxes\TaxGroupController;
use App\Http\Controllers\Taxes\TaxRateController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Controllers\TeamUsers\TeamUserController;
use App\Http\Controllers\Units\UnitController;
use App\Http\Controllers\VariationTemplates\VariationTemplateController;
use App\Http\Controllers\Warranties\WarrantyController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    /** @var \App\Models\User|null $user */
    $user = Auth::user();

    if (! $user) {
        return redirect()->route('login');
    }

    $team = $user->currentTeam ?? $user->personalTeam();

    if (! $team) {
        abort(403);
    }

    return redirect()->route('dashboard', ['current_team' => $team->slug]);
})->name('home');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)
            ->middleware('pos.permission:dashboard.data')
            ->name('dashboard');

        Route::get('kitchen', [KitchenController::class, 'index'])
            ->middleware('pos.permission:kitchen.access')
            ->name('kitchen.index');

        Route::get('order', [OrderController::class, 'index'])
            ->middleware('pos.permission:order.access')
            ->name('order.index');

        Route::get('suppliers/export/{format}', [SupplierController::class, 'exportFile'])
            ->middleware('pos.permission:supplier.view,supplier.view_own')
            ->name('suppliers.export');

        Route::resource('suppliers', SupplierController::class)
            ->middleware('pos.permission:supplier.view,supplier.view_own,supplier.create,supplier.update,supplier.delete')
            ->except([
            'show',
            'create',
            'edit',
        ]);

        Route::post('suppliers/quick-store', [SupplierController::class, 'quickStore'])
            ->name('suppliers.quick-store');

        Route::get('customers/export/{format}', [CustomerController::class, 'exportFile'])
            ->middleware('pos.permission:customer.view,customer.view_own')
            ->name('customers.export');

        Route::resource('customers', CustomerController::class)
            ->middleware('pos.permission:customer.view,customer.view_own,customer.create,customer.update,customer.delete')
            ->except([
            'show',
            'create',
            'edit',
        ]);

        Route::post('customers/quick-store', [CustomerController::class, 'quickStore'])
            ->name('customers.quick-store');

        Route::get('customer-groups/export/{format}', [CustomerGroupController::class, 'exportFile'])
            ->name('customer-groups.export');

        Route::resource('customer-groups', CustomerGroupController::class)
            ->middleware('pos.permission:customer.view,customer.view_own')
            ->except([
            'show',
            'create',
            'edit',
        ]);

        Route::get('warranties/export/{format}', [WarrantyController::class, 'exportFile'])
            ->middleware('pos.permission:warranty.view,warranty.create,warranty.update,warranty.delete')
            ->name('warranties.export');

        Route::resource('warranties', WarrantyController::class)
            ->middleware('pos.permission:warranty.view,warranty.create,warranty.update,warranty.delete')
            ->except([
            'show',
            'create',
            'edit',
        ]);

        Route::get('brands/export/{format}', [BrandController::class, 'exportFile'])
            ->name('brands.export');

        Route::resource('brands', BrandController::class)
            ->middleware('pos.permission:brand.view,brand.create,brand.update,brand.delete')
            ->except([
            'show',
            'create',
            'edit',
        ]);

        Route::get('product-categories/export/{format}', [ProductCategoryController::class, 'exportFile'])
            ->name('product-categories.export');

        Route::resource('product-categories', ProductCategoryController::class)
            ->middleware('pos.permission:category.view,category.create,category.update,category.delete')
            ->except([
            'show',
            'create',
            'edit',
        ]);

        Route::get('units/export/{format}', [UnitController::class, 'exportFile'])
            ->name('units.export');

        Route::resource('units', UnitController::class)
            ->middleware('pos.permission:unit.view,unit.create,unit.update,unit.delete')
            ->except([
            'show',
            'create',
            'edit',
        ]);

        Route::post('brands/quick-store', [BrandController::class, 'quickStore'])
            ->name('brands.quick-store');

        Route::post('units/quick-store', [UnitController::class, 'quickStore'])
            ->name('units.quick-store');

        Route::get('sales-commission-agents/export/{format}', [SalesCommissionAgentController::class, 'exportFile'])
            ->name('sales-commission-agents.export');

        Route::resource('sales-commission-agents', SalesCommissionAgentController::class)->except([
            'show',
            'create',
            'edit',
        ]);

        Route::resource('pos-roles', PosRoleController::class)
            ->middleware('pos.permission:roles.view,roles.create,roles.update,roles.delete')
            ->except(['show']);

        Route::resource('team-users', TeamUserController::class)
            ->middleware('pos.permission:user.view,user.create,user.update,user.delete')
            ->except(['show'])
            ->parameters(['team-users' => 'user']);

        Route::get('taxes', [TaxesController::class, 'index'])
            ->middleware('pos.permission:tax_rate.view')
            ->name('taxes.index');

        Route::get('receipt-printer', [ReceiptPrinterController::class, 'edit'])
            ->middleware('pos.permission:access_printers')
            ->name('receipt-printer.edit');
        Route::patch('receipt-printer', [ReceiptPrinterController::class, 'update'])
            ->middleware('pos.permission:access_printers')
            ->name('receipt-printer.update');

        Route::get('barcode-settings', [BarcodeSettingsController::class, 'edit'])
            ->middleware('pos.permission:barcode_settings.access')
            ->name('barcode-settings.edit');
        Route::patch('barcode-settings', [BarcodeSettingsController::class, 'update'])
            ->middleware('pos.permission:barcode_settings.access')
            ->name('barcode-settings.update');

        Route::get('payment-settings', [PaymentSettingsController::class, 'edit'])
            ->middleware('pos.permission:account.access')
            ->name('payment-settings.edit');
        Route::patch('payment-settings', [PaymentSettingsController::class, 'update'])
            ->middleware('pos.permission:account.access')
            ->name('payment-settings.update');

        Route::get('payment-accounts', [PaymentAccountController::class, 'index'])
            ->middleware('pos.permission:account.access')
            ->name('payment-accounts.index');
        Route::post('account-types', [AccountTypeController::class, 'store'])->name('account-types.store');

        Route::resource('payment-accounts', PaymentAccountController::class)
            ->middleware('pos.permission:account.access')
            ->only([
            'store',
            'update',
            'destroy',
        ]);

        Route::get('purchases', [PurchaseController::class, 'index'])
            ->middleware('pos.permission:purchase.view,view_own_purchase')
            ->name('purchases.index');
        Route::get('purchases/create', [PurchaseController::class, 'create'])
            ->middleware('pos.permission:purchase.create')
            ->name('purchases.create');
        Route::post('purchases', [PurchaseController::class, 'store'])
            ->middleware('pos.permission:purchase.create')
            ->name('purchases.store');
        Route::get('purchases/{purchase}/detail', [PurchaseController::class, 'detail'])->name('purchases.detail');

        Route::get('purchase-returns', [PurchaseReturnController::class, 'index'])
            ->middleware('pos.permission:purchase.view,view_own_purchase')
            ->name('purchase-returns.index');

        Route::get('pos/list', [PosController::class, 'list'])
            ->middleware('pos.permission:sell.view,sell.create')
            ->name('pos.list');
        Route::get('pos/recent-transactions', [PosController::class, 'recentTransactions'])->name('pos.recent-transactions');
        Route::post('pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
        Route::post('cash-register', [CashRegisterController::class, 'store'])->name('cash-register.store');
        Route::post('cash-register/close', [CashRegisterController::class, 'close'])->name('cash-register.close');
        Route::get('pos', [PosController::class, 'index'])
            ->middleware('pos.permission:sell.create')
            ->name('pos.index');

        Route::resource('booking', BookingController::class)
            ->middleware('pos.permission:crud_all_bookings,crud_own_bookings')
            ->except([
            'show',
            'create',
            'edit',
        ]);

        Route::get('sales/drafts', [SaleController::class, 'draftsIndex'])->name('sales.drafts.index');
        Route::get('sales/drafts/create', [SaleController::class, 'createDraft'])->name('sales.drafts.create');
        Route::post('sales/drafts', [SaleController::class, 'storeDraft'])->name('sales.drafts.store');

        Route::get('sales/quotations', [SaleController::class, 'quotationsIndex'])->name('sales.quotations.index');
        Route::get('sales/quotations/create', [SaleController::class, 'createQuotation'])->name('sales.quotations.create');
        Route::post('sales/quotations', [SaleController::class, 'storeQuotation'])->name('sales.quotations.store');

        Route::get('sales/returns', [SaleReturnController::class, 'index'])->name('sales.returns.index');
        Route::get('sales/returns/create', [SaleReturnController::class, 'create'])->name('sales.returns.create');
        Route::post('sales/returns', [SaleReturnController::class, 'store'])->name('sales.returns.store');

        Route::get('sales/shipments', [ShipmentController::class, 'index'])->name('sales.shipments.index');

        Route::get('sales/discounts', [DiscountController::class, 'index'])->name('sales.discounts.index');
        Route::post('sales/discounts', [DiscountController::class, 'store'])->name('sales.discounts.store');

        Route::get('sales/{sale}/detail', [SaleController::class, 'detail'])->name('sales.detail');
        Route::patch('sales/{sale}', [SaleController::class, 'update'])->name('sales.update');
        Route::patch('sales/{sale}/shipping', [SaleController::class, 'updateShipping'])->name('sales.shipping.update');
        Route::delete('sales/{sale}', [SaleController::class, 'destroy'])->name('sales.destroy');
        Route::get('sales/{sale}/documents/invoice', [SaleController::class, 'printInvoice'])->name('sales.documents.invoice');
        Route::get('sales/{sale}/documents/packing-slip', [SaleController::class, 'printPackingSlip'])->name('sales.documents.packing-slip');
        Route::get('sales/{sale}/documents/delivery-note', [SaleController::class, 'printDeliveryNote'])->name('sales.documents.delivery-note');
        Route::get('sales/{sale}/invoice-link', [SaleController::class, 'invoiceLink'])->name('sales.invoice-link');

        Route::get('sales', [SaleController::class, 'index'])
            ->middleware('pos.permission:direct_sell.view,view_own_sell_only')
            ->name('sales.index');
        Route::get('sales/create', [SaleController::class, 'create'])
            ->middleware('pos.permission:direct_sell.access')
            ->name('sales.create');
        Route::post('sales', [SaleController::class, 'store'])->name('sales.store');

        Route::resource('business-locations', BusinessLocationController::class)
            ->middleware('pos.permission:business_settings.access')
            ->except([
            'show',
            'create',
            'edit',
        ]);

        Route::prefix('settings')->group(function () {
            Route::resource('tables', RestaurantTableController::class)
                ->middleware('pos.permission:access_tables')
                ->except(['show', 'create', 'edit'])
                ->parameters(['tables' => 'restaurant_table'])
                ->names('settings.tables');

            Route::resource('modifiers', ModifierSetController::class)
                ->except(['show', 'create', 'edit'])
                ->parameters(['modifiers' => 'modifier_set'])
                ->names('settings.modifiers');
        });

        Route::resource('tax-rates', TaxRateController::class)->only(['store', 'update', 'destroy']);

        Route::resource('tax-groups', TaxGroupController::class)->only(['store', 'update', 'destroy']);

        Route::resource('selling-price-groups', SellingPriceGroupController::class)
            ->middleware('pos.permission:access_default_selling_price')
            ->except([
            'show',
            'create',
            'edit',
        ]);

        Route::resource('variation-templates', VariationTemplateController::class)
            ->middleware('pos.permission:variation.view,variation.create,variation.update,variation.delete')
            ->except([
            'show',
            'create',
            'edit',
        ]);

        Route::get('products/search', [ProductController::class, 'search'])
            ->middleware('pos.permission:product.view')
            ->name('products.search');

        Route::get('products/print-labels', [ProductController::class, 'printLabels'])
            ->middleware('pos.permission:product.print_labels')
            ->name('products.print-labels');

        Route::post('products/import/csv', [ProductController::class, 'importCsv'])
            ->middleware('pos.permission:product.create,product.update')
            ->name('products.import.csv');

        Route::post('products/import/xlsx', [ProductController::class, 'importXlsx'])
            ->middleware('pos.permission:product.create,product.update')
            ->name('products.import.xlsx');

        Route::get('stock-transfers', [StockTransferController::class, 'index'])
            ->middleware('pos.permission:stock_transfer.view,stock_transfer.view_own')
            ->name('stock-transfers.index');
        Route::get('stock-transfers/create', [StockTransferController::class, 'create'])
            ->middleware('pos.permission:stock_transfer.create')
            ->name('stock-transfers.create');
        Route::post('stock-transfers', [StockTransferController::class, 'store'])
            ->middleware('pos.permission:stock_transfer.create')
            ->name('stock-transfers.store');

        Route::get('stock-adjustments', [StockAdjustmentController::class, 'index'])
            ->middleware('pos.permission:stock_adjustment.view,view_own_stock_adjustment')
            ->name('stock-adjustments.index');
        Route::get('stock-adjustments/create', [StockAdjustmentController::class, 'create'])
            ->middleware('pos.permission:stock_adjustment.create')
            ->name('stock-adjustments.create');
        Route::post('stock-adjustments', [StockAdjustmentController::class, 'store'])
            ->middleware('pos.permission:stock_adjustment.create')
            ->name('stock-adjustments.store');

        Route::get('expense-categories', [ExpenseCategoryController::class, 'index'])
            ->middleware('pos.permission:all_expense.access,view_own_expense')
            ->name('expense-categories.index');
        Route::post('expense-categories', [ExpenseCategoryController::class, 'store'])
            ->middleware('pos.permission:expense.add')
            ->name('expense-categories.store');

        Route::get('expenses', [ExpenseController::class, 'index'])
            ->middleware('pos.permission:all_expense.access,view_own_expense')
            ->name('expenses.index');
        Route::get('expenses/create', [ExpenseController::class, 'create'])
            ->middleware('pos.permission:expense.add')
            ->name('expenses.create');
        Route::post('expenses', [ExpenseController::class, 'store'])
            ->middleware('pos.permission:expense.add')
            ->name('expenses.store');

        Route::get('reports/profit-loss', [ProfitLossController::class, 'profitLoss'])
            ->middleware('pos.permission:profit_loss_report.view')
            ->name('reports.profit-loss');

        Route::get('reports/today-profit', [ProfitLossController::class, 'todayProfit'])
            ->name('reports.today-profit');

        Route::get('reports/purchase-sell', [PurchaseSellController::class, 'purchaseSell'])
            ->middleware('pos.permission:purchase_n_sell_report.view')
            ->name('reports.purchase-sell');

        Route::get('reports/tax-report', [TaxReportController::class, 'taxReport'])
            ->middleware('pos.permission:tax_report.view')
            ->name('reports.tax-report');

        Route::get('reports/customer-suppliers', [CustomerSuppliersReportController::class, 'customerSuppliersReport'])
            ->middleware('pos.permission:contacts_report.view')
            ->name('reports.customer-suppliers');

        Route::get('reports/customer-group', [CustomerGroupReportController::class, 'customerGroupReport'])
            ->name('reports.customer-group');

        Route::get('reports/stock', [StockReportController::class, 'stockReport'])
            ->middleware('pos.permission:stock_report.view')
            ->name('reports.stock');

        Route::get('reports/stock-adjustment', [StockAdjustmentReportController::class, 'stockAdjustmentReport'])
            ->name('reports.stock-adjustment');

        Route::get('reports/trending-products', [TrendingProductsReportController::class, 'trendingProducts'])
            ->middleware('pos.permission:trending_product_report.view')
            ->name('reports.trending-products');

        Route::get('reports/items', [ItemsReportController::class, 'itemsReport'])
            ->name('reports.items');

        Route::get('reports/product-purchase', [ProductPurchaseReportController::class, 'productPurchase'])
            ->name('reports.product-purchase');

        Route::get('reports/purchase-payments', [PurchasePaymentReportController::class, 'purchasePayments'])
            ->name('reports.purchase-payments');

        Route::get('reports/sell-payments', [SellPaymentReportController::class, 'sellPayments'])
            ->name('reports.sell-payments');

        Route::get('reports/expense', [ExpenseReportController::class, 'expenseReport'])
            ->middleware('pos.permission:expense_report.view')
            ->name('reports.expense');

        Route::get('reports/register', [RegisterReportController::class, 'registerReport'])
            ->middleware('pos.permission:register_report.view')
            ->name('reports.register');

        Route::get('reports/sales-representative', [SalesRepresentativeReportController::class, 'salesRepresentativeReport'])
            ->middleware('pos.permission:sales_representative.view')
            ->name('reports.sales-representative');

        Route::get('reports/table-report', [TableReportController::class, 'tableReport'])
            ->name('reports.table-report');

        Route::get('reports/service-staff', [ServiceStaffReportController::class, 'serviceStaffReport'])
            ->name('reports.service-staff');

        Route::get('reports/activity-log', [ActivityLogReportController::class, 'activityLog'])
            ->name('reports.activity-log');

        Route::resource('products', ProductController::class)
            ->middleware('pos.permission:product.view,product.create,product.update,product.delete')
            ->except(['show']);
    });

Route::middleware(['auth'])->group(function () {
    Route::get('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
});

require __DIR__.'/settings.php';
