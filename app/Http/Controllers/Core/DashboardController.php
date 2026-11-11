<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderStation;
use App\Models\RestaurantTable;
use App\Models\Role;
use App\Models\User;
use App\Services\BranchContextService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected BranchContextService $branchContext
    ) {}

    public function index(): View
    {
        $user = Auth::user();
        $currentBranchId = $this->branchContext->getActiveBranchId($user);
        $currentBranch = $this->branchContext->getActiveBranch($user);

        // Real counts with low-latency caching
        $stats = Cache::remember("dashboard_stats_branch_{$currentBranchId}", 10, function () use ($currentBranchId) {
            $tablesTotal = RestaurantTable::where('branch_id', $currentBranchId)->count();
            $tablesOccupied = RestaurantTable::where('branch_id', $currentBranchId)->where('status', 'occupied')->count();

            return [
                'branches_count' => Branch::where('is_active', true)->count(),
                'users_total' => User::count(),
                'users_active' => User::where('is_active', true)->count(),
                'branch_users_count' => User::where('branch_id', $currentBranchId)->count(),
                'roles_count' => Role::count(),
                'orders_today' => Order::where('branch_id', $currentBranchId)->whereDate('created_at', today())->count(),
                'tables_total' => $tablesTotal,
                'tables_occupied' => $tablesOccupied,
                'tables_available' => $tablesTotal - $tablesOccupied,
                'stations_count' => OrderStation::where('branch_id', $currentBranchId)->where('is_active', true)->count(),
                'menu_items_count' => MenuItem::where(function ($q) use ($currentBranchId) {
                    $q->where('branch_id', $currentBranchId)->orWhereNull('branch_id');
                })->where('is_active', true)->count(),
            ];
        });

        // Real recent audit activities
        $recentActivities = AuditLog::with(['user', 'branch'])
            ->when(! $user->isSuperAdmin(), function ($query) use ($currentBranchId) {
                $query->where('branch_id', $currentBranchId);
            })
            ->latest('id')
            ->limit(8)
            ->get();

        return view('core.dashboard', [
            'user' => $user,
            'currentBranch' => $currentBranch,
            'stats' => $stats,
            'recentActivities' => $recentActivities,
        ]);
    }
}
