<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Core\AuditLogController;
use App\Http\Controllers\Core\BranchContextController;
use App\Http\Controllers\Core\BranchController;
use App\Http\Controllers\Core\DashboardController;
use App\Http\Controllers\Core\RoleController;
use App\Http\Controllers\Core\SystemSettingController;
use App\Http\Controllers\Core\UserController;
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

    // Core: User Management
    Route::resource('users', UserController::class)->except(['show', 'destroy']);
    Route::post('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');

    // Core: Roles & Permissions
    Route::resource('roles', RoleController::class)->only(['index', 'edit', 'update']);

    // Core: Branch Management
    Route::resource('branches', BranchController::class)->except(['show', 'destroy']);

    // Core: Audit Logs
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

    // Core: Settings
    Route::get('/settings', [SystemSettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SystemSettingController::class, 'update'])->name('settings.update');
});
