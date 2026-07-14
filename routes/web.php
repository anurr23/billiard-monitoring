<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\TableController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\UserController;

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [TableController::class, 'index'])->name('dashboard');
    Route::post('/tables/{id}/start', [TableController::class, 'start'])->name('tables.start');
    Route::post('/tables/{id}/stop', [TableController::class, 'stop'])->name('tables.stop');

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
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
