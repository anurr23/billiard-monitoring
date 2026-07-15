<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\TableController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TransactionItemController;
use App\Http\Controllers\FnbOrderController;
use App\Http\Controllers\ReportController;

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [TableController::class, 'index'])->name('dashboard');
    Route::post('/tables/{id}/start', [TableController::class, 'start'])->name('tables.start');
    Route::post('/tables/{id}/stop', [TableController::class, 'stop'])->name('tables.stop');
    Route::put('/tables/{id}/transactions/{transaction}', [TableController::class, 'updateSession'])->name('tables.updateSession');

    // F&B items on active transactions
    Route::post('/transactions/{transaction}/items', [TransactionItemController::class, 'store'])->name('transaction-items.store');
    Route::patch('/transaction-items/{item}/add', [TransactionItemController::class, 'updateQuantity'])->name('transaction-items.add');
    Route::patch('/transaction-items/{item}', [TransactionItemController::class, 'updateQuantity'])->name('transaction-items.update-quantity');
    Route::delete('/transaction-items/{item}', [TransactionItemController::class, 'destroy'])->name('transaction-items.destroy');

    // F&B standalone orders
    Route::get('/fnb-orders', [FnbOrderController::class, 'index'])->name('fnb-orders.index');
    Route::post('/fnb-orders', [FnbOrderController::class, 'store'])->name('fnb-orders.store');
    Route::post('/fnb-orders/{transaction}/checkout', [FnbOrderController::class, 'checkout'])->name('fnb-orders.checkout');

    Route::middleware('role:admin')->group(function () {
        Route::get('/master/tables', [TableController::class, 'masterIndex'])->name('tables.index');
        Route::post('/master/tables', [TableController::class, 'store'])->name('tables.store');
        Route::put('/master/tables/{table}', [TableController::class, 'update'])->name('tables.update');
        Route::delete('/master/tables/{table}', [TableController::class, 'destroy'])->name('tables.destroy');

        Route::get('/master/packages', [PackageController::class, 'index'])->name('packages.index');
        Route::post('/master/packages', [PackageController::class, 'store'])->name('packages.store');
        Route::put('/master/packages/{package}', [PackageController::class, 'update'])->name('packages.update');
        Route::delete('/master/packages/{package}', [PackageController::class, 'destroy'])->name('packages.destroy');

        Route::get('/master/fnb-items', [\App\Http\Controllers\FnbItemController::class, 'index'])->name('fnb_items.index');
        Route::post('/master/fnb-items', [\App\Http\Controllers\FnbItemController::class, 'store'])->name('fnb_items.store');
        Route::put('/master/fnb-items/{fnbItem}', [\App\Http\Controllers\FnbItemController::class, 'update'])->name('fnb_items.update');
        Route::delete('/master/fnb-items/{fnbItem}', [\App\Http\Controllers\FnbItemController::class, 'destroy'])->name('fnb_items.destroy');
        
        Route::get('/master/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/master/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/master/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/master/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('/master/reports/fnb-sales', [ReportController::class, 'fnbSales'])->name('reports.fnb-sales');
        Route::get('/master/reports/table-transactions', [ReportController::class, 'tableTransactions'])->name('reports.table-transactions');
        Route::get('/master/reports/revenue', [ReportController::class, 'revenue'])->name('reports.revenue');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';
