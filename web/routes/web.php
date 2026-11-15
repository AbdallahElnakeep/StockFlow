<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');





Route::middleware(['guest'])->group(function () {
    Route::get('/login', App\Http\Controllers\Auth\LoginController::class)->name('login');
    Route::post('/login', [App\Http\Controllers\Auth\LoginController::class, 'store']);
});

Route::post('/locale', function (\Illuminate\Http\Request $request) {
    $locale = $request->input('locale') === 'en' ? 'en' : 'ar';
    session(['locale' => $locale]);

    return back();
})->name('locale.switch');

Route::middleware(['auth', 'active.user'])->group(function () {
    Route::post('/logout', [App\Http\Controllers\Auth\LogoutController::class, 'store'])->name('logout');
    Route::get('/dashboard', App\Http\Controllers\DashboardController::class)->name('dashboard');

    Route::middleware('can:users.view')->group(function () {
        Route::get('/users', [App\Http\Controllers\UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [App\Http\Controllers\UserController::class, 'create'])->name('users.create')->middleware('can:users.create');
        Route::post('/users', [App\Http\Controllers\UserController::class, 'store'])->name('users.store')->middleware('can:users.create');
        Route::get('/users/{user}/edit', [App\Http\Controllers\UserController::class, 'edit'])->name('users.edit')->middleware('can:users.update');
        Route::put('/users/{user}', [App\Http\Controllers\UserController::class, 'update'])->name('users.update')->middleware('can:users.update');
        Route::delete('/users/{user}', [App\Http\Controllers\UserController::class, 'destroy'])->name('users.destroy')->middleware('can:users.delete');
    });

    Route::middleware('can:roles.view')->group(function () {
        Route::get('/roles', [App\Http\Controllers\RoleController::class, 'index'])->name('roles.index');
        Route::get('/roles/create', [App\Http\Controllers\RoleController::class, 'create'])->name('roles.create')->middleware('can:roles.create');
        Route::post('/roles', [App\Http\Controllers\RoleController::class, 'store'])->name('roles.store')->middleware('can:roles.create');
        Route::get('/roles/{role}/edit', [App\Http\Controllers\RoleController::class, 'edit'])->name('roles.edit')->middleware('can:roles.update');
        Route::put('/roles/{role}', [App\Http\Controllers\RoleController::class, 'update'])->name('roles.update')->middleware('can:roles.update');
        Route::delete('/roles/{role}', [App\Http\Controllers\RoleController::class, 'destroy'])->name('roles.destroy')->middleware('can:roles.delete');
    });

    Route::middleware('can:warehouses.view')->group(function () {
        Route::get('/warehouses', [App\Http\Controllers\WarehouseController::class, 'index'])->name('warehouses.index');
        Route::get('/warehouses/{warehouse}', [App\Http\Controllers\WarehouseController::class, 'show'])->name('warehouses.show');
        Route::get('/warehouses/create', [App\Http\Controllers\WarehouseController::class, 'create'])->name('warehouses.create')->middleware('can:warehouses.create');
        Route::post('/warehouses', [App\Http\Controllers\WarehouseController::class, 'store'])->name('warehouses.store')->middleware('can:warehouses.create');
        Route::get('/warehouses/{warehouse}/edit', [App\Http\Controllers\WarehouseController::class, 'edit'])->name('warehouses.edit')->middleware('can:warehouses.update');
        Route::put('/warehouses/{warehouse}', [App\Http\Controllers\WarehouseController::class, 'update'])->name('warehouses.update')->middleware('can:warehouses.update');
        Route::delete('/warehouses/{warehouse}', [App\Http\Controllers\WarehouseController::class, 'destroy'])->name('warehouses.destroy')->middleware('can:warehouses.delete');
    });

    Route::middleware('can:products.view')->group(function () {
        Route::get('/products', [App\Http\Controllers\ProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [App\Http\Controllers\ProductController::class, 'create'])->name('products.create')->middleware('can:products.create');
        Route::post('/products', [App\Http\Controllers\ProductController::class, 'store'])->name('products.store')->middleware('can:products.create');
        Route::get('/products/{product}/edit', [App\Http\Controllers\ProductController::class, 'edit'])->name('products.edit')->middleware('can:products.update');
        Route::put('/products/{product}', [App\Http\Controllers\ProductController::class, 'update'])->name('products.update')->middleware('can:products.update');
        Route::delete('/products/{product}', [App\Http\Controllers\ProductController::class, 'destroy'])->name('products.destroy')->middleware('can:products.delete');

        Route::get('/categories', [App\Http\Controllers\CategoryController::class, 'index'])->name('categories.index');
        Route::get('/categories/create', [App\Http\Controllers\CategoryController::class, 'create'])->name('categories.create')->middleware('can:products.create');
        Route::post('/categories', [App\Http\Controllers\CategoryController::class, 'store'])->name('categories.store')->middleware('can:products.create');
        Route::get('/categories/{category}/edit', [App\Http\Controllers\CategoryController::class, 'edit'])->name('categories.edit')->middleware('can:products.update');
        Route::put('/categories/{category}', [App\Http\Controllers\CategoryController::class, 'update'])->name('categories.update')->middleware('can:products.update');
        Route::delete('/categories/{category}', [App\Http\Controllers\CategoryController::class, 'destroy'])->name('categories.destroy')->middleware('can:products.delete');

        Route::get('/units', [App\Http\Controllers\UnitController::class, 'index'])->name('units.index');
        Route::get('/units/create', [App\Http\Controllers\UnitController::class, 'create'])->name('units.create')->middleware('can:products.create');
        Route::post('/units', [App\Http\Controllers\UnitController::class, 'store'])->name('units.store')->middleware('can:products.create');
        Route::get('/units/{unit}/edit', [App\Http\Controllers\UnitController::class, 'edit'])->name('units.edit')->middleware('can:products.update');
        Route::put('/units/{unit}', [App\Http\Controllers\UnitController::class, 'update'])->name('units.update')->middleware('can:products.update');
        Route::delete('/units/{unit}', [App\Http\Controllers\UnitController::class, 'destroy'])->name('units.destroy')->middleware('can:products.delete');
    });

    Route::middleware('can:suppliers.view')->group(function () {
        Route::get('/suppliers', [App\Http\Controllers\SupplierController::class, 'index'])->name('suppliers.index');
        Route::get('/suppliers/create', [App\Http\Controllers\SupplierController::class, 'create'])->name('suppliers.create')->middleware('can:purchases.create');
        Route::post('/suppliers', [App\Http\Controllers\SupplierController::class, 'store'])->name('suppliers.store')->middleware('can:purchases.create');
        Route::get('/suppliers/{supplier}/edit', [App\Http\Controllers\SupplierController::class, 'edit'])->name('suppliers.edit')->middleware('can:purchases.update');
        Route::put('/suppliers/{supplier}', [App\Http\Controllers\SupplierController::class, 'update'])->name('suppliers.update')->middleware('can:purchases.update');
        Route::delete('/suppliers/{supplier}', [App\Http\Controllers\SupplierController::class, 'destroy'])->name('suppliers.destroy')->middleware('can:purchases.delete');
    });

    Route::middleware('can:purchases.view')->group(function () {
        Route::get('/purchases', [App\Http\Controllers\PurchaseController::class, 'index'])->name('purchases.index');
        Route::get('/purchases/create', [App\Http\Controllers\PurchaseController::class, 'create'])->name('purchases.create')->middleware('can:purchases.create');
        Route::post('/purchases', [App\Http\Controllers\PurchaseController::class, 'store'])->name('purchases.store')->middleware('can:purchases.create');
        Route::get('/purchases/{purchase}', [App\Http\Controllers\PurchaseController::class, 'show'])->name('purchases.show');
    });

    Route::middleware('can:inventory.view')->group(function () {
        Route::get('/inventory/balances', [App\Http\Controllers\InventoryController::class, 'balances'])->name('inventory.balances');
        Route::get('/inventory/movements', [App\Http\Controllers\InventoryController::class, 'movements'])->name('inventory.movements');
        Route::get('/transfers', [App\Http\Controllers\TransferController::class, 'index'])->name('transfers.index');
        Route::get('/transfers/create', [App\Http\Controllers\TransferController::class, 'create'])->name('transfers.create')->middleware('can:inventory.create');
        Route::post('/transfers', [App\Http\Controllers\TransferController::class, 'store'])->name('transfers.store')->middleware('can:inventory.create');
        Route::get('/transfers/{transfer}', [App\Http\Controllers\TransferController::class, 'show'])->name('transfers.show');
        Route::get('/counts', [App\Http\Controllers\StockCountController::class, 'index'])->name('counts.index');
        Route::get('/counts/create', [App\Http\Controllers\StockCountController::class, 'create'])->name('counts.create')->middleware('can:inventory.create');
        Route::post('/counts', [App\Http\Controllers\StockCountController::class, 'store'])->name('counts.store')->middleware('can:inventory.create');
        Route::get('/counts/{count}', [App\Http\Controllers\StockCountController::class, 'show'])->name('counts.show');
        Route::put('/counts/{count}', [App\Http\Controllers\StockCountController::class, 'update'])->name('counts.update')->middleware('can:inventory.update');
        Route::post('/counts/{count}/adjust', [App\Http\Controllers\StockCountController::class, 'adjust'])->name('counts.adjust')->middleware('can:inventory.update');
    });

    Route::middleware('can:customers.view')->group(function () {
        Route::get('/customers', [App\Http\Controllers\CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/create', [App\Http\Controllers\CustomerController::class, 'create'])->name('customers.create')->middleware('can:customers.create');
        Route::post('/customers', [App\Http\Controllers\CustomerController::class, 'store'])->name('customers.store')->middleware('can:customers.create');
        Route::post('/customers/import', [App\Http\Controllers\CustomerController::class, 'import'])->name('customers.import')->middleware('can:customers.create');
        Route::get('/customers/import/form', [App\Http\Controllers\CustomerController::class, 'importForm'])->name('customers.import.form')->middleware('can:customers.create');
        Route::get('/customers/{customer}/edit', [App\Http\Controllers\CustomerController::class, 'edit'])->name('customers.edit')->middleware('can:customers.update');
        Route::put('/customers/{customer}', [App\Http\Controllers\CustomerController::class, 'update'])->name('customers.update')->middleware('can:customers.update');
        Route::delete('/customers/{customer}', [App\Http\Controllers\CustomerController::class, 'destroy'])->name('customers.destroy')->middleware('can:customers.delete');
    });

    Route::middleware('can:sales.view')->group(function () {
        Route::get('/invoices', [App\Http\Controllers\SalesInvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/invoices/create', [App\Http\Controllers\SalesInvoiceController::class, 'create'])->name('invoices.create')->middleware('can:sales.create');
        Route::post('/invoices', [App\Http\Controllers\SalesInvoiceController::class, 'store'])->name('invoices.store')->middleware('can:sales.create');
        Route::get('/invoices/{invoice}', [App\Http\Controllers\SalesInvoiceController::class, 'show'])->name('invoices.show');
        Route::get('/invoices/{invoice}/print', [App\Http\Controllers\SalesInvoiceController::class, 'print'])->name('invoices.print');
        Route::post('/invoices/{invoice}/cancel', [App\Http\Controllers\SalesInvoiceController::class, 'cancel'])->name('invoices.cancel')->middleware('can:sales.delete');

        Route::get('/returns', [App\Http\Controllers\SalesReturnController::class, 'index'])->name('returns.index');
        Route::get('/returns/create', [App\Http\Controllers\SalesReturnController::class, 'create'])->name('returns.create')->middleware('can:returns.create');
        Route::post('/returns', [App\Http\Controllers\SalesReturnController::class, 'store'])->name('returns.store')->middleware('can:returns.create');
    });

    Route::middleware('can:payments.view')->group(function () {
        Route::get('/payments', [App\Http\Controllers\PaymentController::class, 'index'])->name('payments.index');
        Route::get('/payments/create', [App\Http\Controllers\PaymentController::class, 'create'])->name('payments.create')->middleware('can:payments.create');
        Route::post('/payments', [App\Http\Controllers\PaymentController::class, 'store'])->name('payments.store')->middleware('can:payments.create');
    });

    Route::middleware('can:reps.view')->group(function () {
        Route::get('/reps', [App\Http\Controllers\RepController::class, 'index'])->name('reps.index');
        Route::get('/reps/{user}', [App\Http\Controllers\RepController::class, 'show'])->name('reps.show');
        Route::post('/reps/{user}/settle', [App\Http\Controllers\RepController::class, 'settle'])->name('reps.settle')->middleware('can:reps.update');
        Route::get('/loadings', [App\Http\Controllers\RepLoadingController::class, 'index'])->name('loadings.index');
        Route::get('/loadings/create', [App\Http\Controllers\RepLoadingController::class, 'create'])->name('loadings.create')->middleware('can:reps.create');
        Route::post('/loadings', [App\Http\Controllers\RepLoadingController::class, 'store'])->name('loadings.store')->middleware('can:reps.create');
        Route::get('/loadings/{loading}', [App\Http\Controllers\RepLoadingController::class, 'show'])->name('loadings.show');
        Route::get('/settlements', [App\Http\Controllers\RepSettlementController::class, 'index'])->name('settlements.index');
        Route::get('/settlements/{settlement}', [App\Http\Controllers\RepSettlementController::class, 'show'])->name('settlements.show');
        Route::get('/expenses', [App\Http\Controllers\ExpenseController::class, 'index'])->name('expenses.index');
        Route::get('/expenses/create', [App\Http\Controllers\ExpenseController::class, 'create'])->name('expenses.create')->middleware('can:expenses.create');
        Route::post('/expenses', [App\Http\Controllers\ExpenseController::class, 'store'])->name('expenses.store')->middleware('can:expenses.create');
    });

    Route::middleware('can:reports.view')->group(function () {
        Route::get('/reports/sales', [App\Http\Controllers\ReportController::class, 'sales'])->name('reports.sales');
        Route::get('/reports/products', [App\Http\Controllers\ReportController::class, 'products'])->name('reports.products');
        Route::get('/reports/reps', [App\Http\Controllers\ReportController::class, 'reps'])->name('reports.reps');
        Route::get('/reports/aging', [App\Http\Controllers\ReportController::class, 'aging'])->name('reports.aging');
        Route::get('/reports/profit', [App\Http\Controllers\ReportController::class, 'profit'])->name('reports.profit');
        Route::get('/reports/stock', [App\Http\Controllers\ReportController::class, 'stock'])->name('reports.stock');
        Route::get('/reports/cheques', [App\Http\Controllers\ReportController::class, 'cheques'])->name('reports.cheques');
        Route::get('/reports/statement/{customer}', [App\Http\Controllers\ReportController::class, 'statement'])->name('reports.statement');
    });

    Route::middleware('can:settings.view')->group(function () {
        Route::get('/settings', [App\Http\Controllers\SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings', [App\Http\Controllers\SettingsController::class, 'update'])->name('settings.update');
    });
});
