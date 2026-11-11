<?php

use App\Http\Controllers\Admin\StationMappingController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Core\AuditLogController;
use App\Http\Controllers\Core\BranchContextController;
use App\Http\Controllers\Core\BranchController;
use App\Http\Controllers\Core\DashboardController;
use App\Http\Controllers\Core\RoleController;
use App\Http\Controllers\Core\SystemSettingController;
use App\Http\Controllers\Core\UserController;
use App\Http\Controllers\Fb\RecipeController;
use App\Http\Controllers\Finance\FinanceController;
use App\Http\Controllers\Hr\HrController;
use App\Http\Controllers\Inventory\InventoryController;
use App\Http\Controllers\Pos\MenuController;
use App\Http\Controllers\Pos\OrderController;
use App\Http\Controllers\Pos\PosController;
use App\Http\Controllers\Pos\TableController;
use App\Http\Controllers\Procurement\ProcurementController;
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
    Route::post('/pos/orders/{order}/add-items', [PosController::class, 'addItemsToOrder'])->name('pos.orders.add-items');
    Route::post('/pos/orders/{order}/settle', [PosController::class, 'settleOrder'])->name('pos.orders.settle');

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

    // Admin Panel: Station Mapping (Thermal Order Printers - Model IRP 200D)
    Route::get('/admin/stations', [StationMappingController::class, 'index'])->name('admin.stations.index');
    Route::post('/admin/stations', [StationMappingController::class, 'store'])->name('admin.stations.store');
    Route::put('/admin/stations/{station}', [StationMappingController::class, 'update'])->name('admin.stations.update');
    Route::delete('/admin/stations/{station}', [StationMappingController::class, 'destroy'])->name('admin.stations.destroy');
    Route::post('/admin/stations/{station}/toggle', [StationMappingController::class, 'toggle'])->name('admin.stations.toggle');

    // Admin Panel: Core & Administration
    Route::resource('users', UserController::class)->except(['show', 'destroy']);
    Route::post('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');

    Route::resource('roles', RoleController::class)->only(['index', 'edit', 'update']);

    Route::resource('branches', BranchController::class)->except(['show', 'destroy']);

    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

    Route::get('/settings', [SystemSettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SystemSettingController::class, 'update'])->name('settings.update');

    // Phase 4: Stock & Inventory
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory/{item}/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');

    // Phase 4: Procurement
    Route::get('/procurement', [ProcurementController::class, 'index'])->name('procurement.index');
    Route::post('/procurement/orders', [ProcurementController::class, 'storeOrder'])->name('procurement.orders.store');

    // Phase 5: F&B Recipes & Costing
    Route::get('/fb/recipes', [RecipeController::class, 'index'])->name('fb.recipes.index');

    // Phase 6: Finance & Accounting
    Route::get('/finance', [FinanceController::class, 'index'])->name('finance.index');
    Route::post('/finance/expenses', [FinanceController::class, 'storeExpense'])->name('finance.expenses.store');

    // Phase 7: Human Resources (HR)
    Route::get('/hr', [HrController::class, 'index'])->name('hr.index');
    Route::post('/hr/attendance', [HrController::class, 'recordAttendance'])->name('hr.attendance.record');
});
