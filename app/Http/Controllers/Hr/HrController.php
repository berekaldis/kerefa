<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\EmployeeAttendance;
use App\Models\Order;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\BranchContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class HrController extends Controller
{
    public function __construct(
        protected BranchContextService $branchContext,
        protected AuditLogService $auditLog
    ) {}

    public function index(): View
    {
        $this->authorize('permission', 'hr.employees.view');

        $user = Auth::user();
        $branchId = $this->branchContext->getActiveBranchId($user);

        $employees = User::with(['department', 'roles'])
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)->orWhereNull('branch_id');
            })
            ->where('is_active', true)
            ->paginate(15);

        $departments = Department::withCount('users')->where('is_active', true)->get();

        $todayAttendances = EmployeeAttendance::with('user')
            ->whereDate('date', today())
            ->get();

        // Waiter / Server Sales Performance
        $waiterPerformance = Order::selectRaw('waiter_id, COUNT(*) as orders_count, SUM(total_amount) as total_revenue')
            ->whereNotNull('waiter_id')
            ->where('status', 'completed')
            ->groupBy('waiter_id')
            ->with('waiter')
            ->orderByDesc('total_revenue')
            ->take(5)
            ->get();

        $stats = [
            'total_staff' => User::where('is_active', true)->count(),
            'present_today' => $todayAttendances->where('status', 'present')->count(),
            'departments_count' => Department::count(),
        ];

        return view('hr.index', compact('employees', 'departments', 'todayAttendances', 'waiterPerformance', 'stats'));
    }

    public function recordAttendance(Request $request): RedirectResponse
    {
        $this->authorize('permission', 'hr.attendance');

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'status' => ['required', 'in:present,late,absent,on_leave'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        EmployeeAttendance::updateOrCreate(
            ['user_id' => $validated['user_id'], 'date' => today()->toDateString()],
            [
                'check_in' => now()->toTimeString(),
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
            ]
        );

        return redirect()->route('hr.index')
            ->with('success', __('Staff attendance recorded successfully.'));
    }
}
