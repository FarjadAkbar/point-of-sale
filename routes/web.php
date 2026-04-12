<?php

use App\Http\Controllers\Brands\BrandController;
use App\Http\Controllers\BusinessLocations\BusinessLocationController;
use App\Http\Controllers\CustomerGroups\CustomerGroupController;
use App\Http\Controllers\Customers\CustomerController;
use App\Http\Controllers\Expenses\ExpenseCategoryController;
use App\Http\Controllers\Expenses\ExpenseController;
use App\Http\Controllers\PaymentAccounts\AccountTypeController;
use App\Http\Controllers\PaymentAccounts\PaymentAccountController;
use App\Http\Controllers\Pos\PosController;
use App\Http\Controllers\ProductCategories\ProductCategoryController;
use App\Http\Controllers\Products\ProductController;
use App\Http\Controllers\Purchases\PurchaseController;
use App\Http\Controllers\Purchases\PurchaseReturnController;
use App\Http\Controllers\Reports\CustomerGroupReportController;
use App\Http\Controllers\Reports\CustomerSuppliersReportController;
use App\Http\Controllers\Reports\ExpenseReportController;
use App\Http\Controllers\Reports\ItemsReportController;
use App\Http\Controllers\Reports\ProfitLossController;
use App\Http\Controllers\Reports\RegisterReportController;
use App\Http\Controllers\Reports\PurchasePaymentReportController;
use App\Http\Controllers\Reports\PurchaseSellController;
use App\Http\Controllers\Reports\SalesRepresentativeReportController;
use App\Http\Controllers\Reports\SellPaymentReportController;
use App\Http\Controllers\Reports\StockAdjustmentReportController;
use App\Http\Controllers\Reports\StockReportController;
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
use App\Http\Controllers\Taxes\TaxesController;
use App\Http\Controllers\Taxes\TaxGroupController;
use App\Http\Controllers\Taxes\TaxRateController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Controllers\Units\UnitController;
use App\Http\Controllers\VariationTemplates\VariationTemplateController;
use App\Http\Controllers\Warranties\WarrantyController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $user = auth()->user();

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
        Route::inertia('dashboard', 'Dashboard')->name('dashboard');

        Route::get('suppliers/export/{format}', [SupplierController::class, 'exportFile'])
            ->name('suppliers.export');

        Route::resource('suppliers', SupplierController::class)->except([
            'show',
            'create',
            'edit',
        ]);

        Route::post('suppliers/quick-store', [SupplierController::class, 'quickStore'])
            ->name('suppliers.quick-store');

        Route::get('customers/export/{format}', [CustomerController::class, 'exportFile'])
            ->name('customers.export');

        Route::resource('customers', CustomerController::class)->except([
            'show',
            'create',
            'edit',
        ]);

        Route::post('customers/quick-store', [CustomerController::class, 'quickStore'])
            ->name('customers.quick-store');

        Route::get('customer-groups/export/{format}', [CustomerGroupController::class, 'exportFile'])
            ->name('customer-groups.export');

        Route::resource('customer-groups', CustomerGroupController::class)->except([
            'show',
            'create',
            'edit',
        ]);

        Route::get('warranties/export/{format}', [WarrantyController::class, 'exportFile'])
            ->name('warranties.export');

        Route::resource('warranties', WarrantyController::class)->except([
            'show',
            'create',
            'edit',
        ]);

        Route::get('brands/export/{format}', [BrandController::class, 'exportFile'])
            ->name('brands.export');

        Route::resource('brands', BrandController::class)->except([
            'show',
            'create',
            'edit',
        ]);

        Route::get('product-categories/export/{format}', [ProductCategoryController::class, 'exportFile'])
            ->name('product-categories.export');

        Route::resource('product-categories', ProductCategoryController::class)->except([
            'show',
            'create',
            'edit',
        ]);

        Route::get('units/export/{format}', [UnitController::class, 'exportFile'])
            ->name('units.export');

        Route::resource('units', UnitController::class)->except([
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

        Route::get('taxes', [TaxesController::class, 'index'])->name('taxes.index');

        Route::get('receipt-printer', [ReceiptPrinterController::class, 'edit'])->name('receipt-printer.edit');
        Route::patch('receipt-printer', [ReceiptPrinterController::class, 'update'])->name('receipt-printer.update');

        Route::get('barcode-settings', [BarcodeSettingsController::class, 'edit'])->name('barcode-settings.edit');
        Route::patch('barcode-settings', [BarcodeSettingsController::class, 'update'])->name('barcode-settings.update');

        Route::get('payment-settings', [PaymentSettingsController::class, 'edit'])->name('payment-settings.edit');
        Route::patch('payment-settings', [PaymentSettingsController::class, 'update'])->name('payment-settings.update');

        Route::get('payment-accounts', [PaymentAccountController::class, 'index'])->name('payment-accounts.index');
        Route::post('account-types', [AccountTypeController::class, 'store'])->name('account-types.store');

        Route::resource('payment-accounts', PaymentAccountController::class)->only([
            'store',
            'update',
            'destroy',
        ]);

        Route::get('purchases', [PurchaseController::class, 'index'])->name('purchases.index');
        Route::get('purchases/create', [PurchaseController::class, 'create'])->name('purchases.create');
        Route::post('purchases', [PurchaseController::class, 'store'])->name('purchases.store');

        Route::get('purchase-returns', [PurchaseReturnController::class, 'index'])->name('purchase-returns.index');

        Route::get('pos/list', [PosController::class, 'list'])->name('pos.list');
        Route::post('pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
        Route::get('pos', [PosController::class, 'index'])->name('pos.index');

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

        Route::get('sales', [SaleController::class, 'index'])->name('sales.index');
        Route::get('sales/create', [SaleController::class, 'create'])->name('sales.create');
        Route::post('sales', [SaleController::class, 'store'])->name('sales.store');

        Route::resource('business-locations', BusinessLocationController::class)->except([
            'show',
            'create',
            'edit',
        ]);

        Route::resource('tax-rates', TaxRateController::class)->only(['store', 'update', 'destroy']);

        Route::resource('tax-groups', TaxGroupController::class)->only(['store', 'update', 'destroy']);

        Route::resource('selling-price-groups', SellingPriceGroupController::class)->except([
            'show',
            'create',
            'edit',
        ]);

        Route::resource('variation-templates', VariationTemplateController::class)->except([
            'show',
            'create',
            'edit',
        ]);

        Route::get('products/search', [ProductController::class, 'search'])
            ->name('products.search');

        Route::get('products/print-labels', [ProductController::class, 'printLabels'])
            ->name('products.print-labels');

        Route::post('products/import/csv', [ProductController::class, 'importCsv'])
            ->name('products.import.csv');

        Route::post('products/import/xlsx', [ProductController::class, 'importXlsx'])
            ->name('products.import.xlsx');

        Route::get('stock-transfers', [StockTransferController::class, 'index'])->name('stock-transfers.index');
        Route::get('stock-transfers/create', [StockTransferController::class, 'create'])->name('stock-transfers.create');
        Route::post('stock-transfers', [StockTransferController::class, 'store'])->name('stock-transfers.store');

        Route::get('stock-adjustments', [StockAdjustmentController::class, 'index'])->name('stock-adjustments.index');
        Route::get('stock-adjustments/create', [StockAdjustmentController::class, 'create'])->name('stock-adjustments.create');
        Route::post('stock-adjustments', [StockAdjustmentController::class, 'store'])->name('stock-adjustments.store');

        Route::get('expense-categories', [ExpenseCategoryController::class, 'index'])->name('expense-categories.index');
        Route::post('expense-categories', [ExpenseCategoryController::class, 'store'])->name('expense-categories.store');

        Route::get('expenses', [ExpenseController::class, 'index'])->name('expenses.index');
        Route::get('expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
        Route::post('expenses', [ExpenseController::class, 'store'])->name('expenses.store');

        Route::get('reports/profit-loss', [ProfitLossController::class, 'profitLoss'])
            ->name('reports.profit-loss');

        Route::get('reports/purchase-sell', [PurchaseSellController::class, 'purchaseSell'])
            ->name('reports.purchase-sell');

        Route::get('reports/tax-report', [TaxReportController::class, 'taxReport'])
            ->name('reports.tax-report');

        Route::get('reports/customer-suppliers', [CustomerSuppliersReportController::class, 'customerSuppliersReport'])
            ->name('reports.customer-suppliers');

        Route::get('reports/customer-group', [CustomerGroupReportController::class, 'customerGroupReport'])
            ->name('reports.customer-group');

        Route::get('reports/stock', [StockReportController::class, 'stockReport'])
            ->name('reports.stock');

        Route::get('reports/stock-adjustment', [StockAdjustmentReportController::class, 'stockAdjustmentReport'])
            ->name('reports.stock-adjustment');

        Route::get('reports/trending-products', [TrendingProductsReportController::class, 'trendingProducts'])
            ->name('reports.trending-products');

        Route::get('reports/items', [ItemsReportController::class, 'itemsReport'])
            ->name('reports.items');

        Route::get('reports/purchase-payments', [PurchasePaymentReportController::class, 'purchasePayments'])
            ->name('reports.purchase-payments');

        Route::get('reports/sell-payments', [SellPaymentReportController::class, 'sellPayments'])
            ->name('reports.sell-payments');

        Route::get('reports/expense', [ExpenseReportController::class, 'expenseReport'])
            ->name('reports.expense');

        Route::get('reports/register', [RegisterReportController::class, 'registerReport'])
            ->name('reports.register');

        Route::get('reports/sales-representative', [SalesRepresentativeReportController::class, 'salesRepresentativeReport'])
            ->name('reports.sales-representative');

        Route::resource('products', ProductController::class)->except(['show']);
    });

Route::middleware(['auth'])->group(function () {
    Route::get('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
});

require __DIR__.'/settings.php';
