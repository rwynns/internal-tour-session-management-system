<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AttractionController;
use App\Http\Controllers\CashierDashboardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
});

Route::middleware(['auth', 'verified', 'role:recreation_admin'])->group(function () {
    Route::resource('attractions', AttractionController::class)->except(['show']);
    Route::patch('attractions/{attraction}/toggle-active', [AttractionController::class, 'toggleActive'])
        ->name('attractions.toggle-active');

    Route::resource('sessions', SessionController::class)->except(['show']);
    Route::patch('sessions/{session}/status', [SessionController::class, 'updateStatus'])
        ->name('sessions.update-status');

    Route::get('activity-logs', [ActivityLogController::class, 'index'])
        ->name('activity-logs.index');
});

Route::middleware(['auth', 'verified', 'role:cashier'])->group(function () {
    Route::post('allocations/{session}', [CashierDashboardController::class, 'store'])
        ->name('allocations.store');
    Route::patch('allocations/{allocation}/cancel', [CashierDashboardController::class, 'cancel'])
        ->name('allocations.cancel');
    Route::patch('allocations/{allocation}/move', [CashierDashboardController::class, 'move'])
        ->name('allocations.move');
});

require __DIR__.'/settings.php';
