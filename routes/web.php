<?php

use App\Http\Controllers\Brands\BrandController;
use App\Http\Controllers\BusinessLocations\BusinessLocationController;
use App\Http\Controllers\CustomerGroups\CustomerGroupController;
use App\Http\Controllers\Customers\CustomerController;
use App\Http\Controllers\PaymentAccounts\PaymentAccountController;
use App\Http\Controllers\ProductCategories\ProductCategoryController;
use App\Http\Controllers\Products\ProductController;
use App\Http\Controllers\Purchases\PurchaseController;
use App\Http\Controllers\Purchases\PurchaseReturnController;
use App\Http\Controllers\Sales\SaleController;
use App\Http\Controllers\SalesCommissionAgents\SalesCommissionAgentController;
use App\Http\Controllers\SellingPriceGroups\SellingPriceGroupController;
use App\Http\Controllers\Settings\BarcodeSettingsController;
use App\Http\Controllers\Settings\PaymentSettingsController;
use App\Http\Controllers\Settings\ReceiptPrinterController;
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

        Route::resource('payment-accounts', PaymentAccountController::class)->only([
            'store',
            'update',
            'destroy',
        ]);

        Route::get('purchases', [PurchaseController::class, 'index'])->name('purchases.index');
        Route::get('purchases/create', [PurchaseController::class, 'create'])->name('purchases.create');
        Route::post('purchases', [PurchaseController::class, 'store'])->name('purchases.store');

        Route::get('purchase-returns', [PurchaseReturnController::class, 'index'])->name('purchase-returns.index');

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

        Route::resource('products', ProductController::class)->except(['show', 'edit', 'update', 'destroy']);
    });

Route::middleware(['auth'])->group(function () {
    Route::get('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
});

require __DIR__.'/settings.php';
