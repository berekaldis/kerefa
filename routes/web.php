<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Core\AuditLogController;
use App\Http\Controllers\Core\BranchContextController;
use App\Http\Controllers\Core\BranchController;
use App\Http\Controllers\Core\DashboardController;
use App\Http\Controllers\Core\RoleController;
use App\Http\Controllers\Core\SystemSettingController;
use App\Http\Controllers\Core\UserController;
use App\Http\Controllers\Pos\BarDisplayController;
use App\Http\Controllers\Pos\KitchenDisplayController;
use App\Http\Controllers\Pos\MenuController;
use App\Http\Controllers\Pos\OrderController;
use App\Http\Controllers\Pos\PosController;
use App\Http\Controllers\Pos\TableController;
use Illuminate\Support\Facades\Route;

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// Authenticated ERP Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/profile', [AuthController::class, 'showProfile'])->name('profile');
    Route::put('/profile/password', [AuthController::class, 'updatePassword'])->name('profile.password');

    // Branch context switching
    Route::post('/branches/switch', [BranchContextController::class, 'switchBranch'])->name('branches.switch');

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Phase 3: RMS / POS Module
    Route::get('/pos', [PosController::class, 'terminal'])->name('pos.terminal');
    Route::post('/pos/orders', [PosController::class, 'storeOrder'])->name('pos.orders.store');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

    Route::get('/tables', [TableController::class, 'index'])->name('tables.index');
    Route::post('/tables', [TableController::class, 'store'])->name('tables.store');
    Route::put('/tables/{table}', [TableController::class, 'update'])->name('tables.update');
    Route::post('/tables/{table}/transfer', [TableController::class, 'transfer'])->name('tables.transfer');
    Route::post('/tables/{table}/merge', [TableController::class, 'merge'])->name('tables.merge');

    Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');
    Route::post('/menu', [MenuController::class, 'store'])->name('menu.store');
    Route::put('/menu/{menu}', [MenuController::class, 'update'])->name('menu.update');
    Route::post('/menu/{menu}/toggle-availability', [MenuController::class, 'toggleAvailability'])->name('menu.toggle-availability');

    Route::get('/kds', [KitchenDisplayController::class, 'index'])->name('kds.index');
    Route::post('/kds/{ticket}/status', [KitchenDisplayController::class, 'updateStatus'])->name('kds.ticket.status');

    Route::get('/bds', [BarDisplayController::class, 'index'])->name('bds.index');
    Route::post('/bds/{ticket}/status', [BarDisplayController::class, 'updateStatus'])->name('bds.ticket.status');

    // Phase 2: Core & Administration
    Route::resource('users', UserController::class)->except(['show', 'destroy']);
    Route::post('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');

    Route::resource('roles', RoleController::class)->only(['index', 'edit', 'update']);

    Route::resource('branches', BranchController::class)->except(['show', 'destroy']);

    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

    Route::get('/settings', [SystemSettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SystemSettingController::class, 'update'])->name('settings.update');
});
