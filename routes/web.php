<?php

use App\Http\Controllers\Brands\BrandController;
use App\Http\Controllers\CustomerGroups\CustomerGroupController;
use App\Http\Controllers\Customers\CustomerController;
use App\Http\Controllers\ProductCategories\ProductCategoryController;
use App\Http\Controllers\Products\ProductController;
use App\Http\Controllers\SellingPriceGroups\SellingPriceGroupController;
use App\Http\Controllers\Suppliers\SupplierController;
use App\Http\Controllers\Units\UnitController;
use App\Http\Controllers\VariationTemplates\VariationTemplateController;
use App\Http\Controllers\Warranties\WarrantyController;
use App\Http\Controllers\Teams\TeamInvitationController;
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

        Route::get('customers/export/{format}', [CustomerController::class, 'exportFile'])
            ->name('customers.export');

        Route::resource('customers', CustomerController::class)->except([
            'show',
            'create',
            'edit',
        ]);

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
