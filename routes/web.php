<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\TableController;
use App\Http\Controllers\PackageController;

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [TableController::class, 'index'])->name('dashboard');
    Route::post('/tables/{id}/start', [TableController::class, 'start'])->name('tables.start');
    Route::post('/tables/{id}/stop', [TableController::class, 'stop'])->name('tables.stop');

    Route::get('/master/tables', [TableController::class, 'masterIndex'])->name('tables.index');
    Route::post('/master/tables', [TableController::class, 'store'])->name('tables.store');
    Route::delete('/master/tables/{table}', [TableController::class, 'destroy'])->name('tables.destroy');

    Route::get('/master/packages', [PackageController::class, 'index'])->name('packages.index');
    Route::post('/master/packages', [PackageController::class, 'store'])->name('packages.store');
    Route::delete('/master/packages/{package}', [PackageController::class, 'destroy'])->name('packages.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
