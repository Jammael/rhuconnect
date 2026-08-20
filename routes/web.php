<?php

use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SessionCloseController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'idle', 'verified', 'active'])
    ->name('dashboard');

Route::get('/admin/dashboard', [DashboardController::class, 'admin'])
    ->middleware(['auth', 'idle', 'verified', 'active', 'role:Administrator'])
    ->name('admin.dashboard');

Route::post('/session/close-beacon', SessionCloseController::class)
    ->name('session.close-beacon');

Route::middleware(['auth', 'idle', 'verified', 'active', 'role:Administrator'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('users', UserManagementController::class)->except(['destroy']);
        Route::patch('users/{user}/status', [UserManagementController::class, 'updateStatus'])
            ->name('users.status');
    });

Route::middleware(['auth', 'idle', 'active'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::post('/session/heartbeat', fn () => response()->noContent())->name('session.heartbeat');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
