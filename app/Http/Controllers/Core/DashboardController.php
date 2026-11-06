<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Services\BranchContextService;
use Illuminate\Support\Facades\Auth;
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

        // Real counts
        $stats = [
            'branches_count' => Branch::where('is_active', true)->count(),
            'users_total' => User::count(),
            'users_active' => User::where('is_active', true)->count(),
            'branch_users_count' => User::where('branch_id', $currentBranchId)->count(),
            'departments_count' => Department::where(function ($q) use ($currentBranchId) {
                $q->where('branch_id', $currentBranchId)->orWhereNull('branch_id');
            })->count(),
            'roles_count' => Role::count(),
        ];

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
