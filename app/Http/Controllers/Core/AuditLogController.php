<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('permission', 'audit.view');

        $query = AuditLog::with(['user', 'branch']);

        if ($request->filled('module')) {
            $query->where('module', $request->input('module'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $logs = $query->latest('id')->paginate(20)->withQueryString();

        $modules = AuditLog::select('module')->distinct()->pluck('module');
        $actions = AuditLog::select('action')->distinct()->pluck('action');
        $users = User::orderBy('name')->get();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('core.audit_logs.index', [
            'logs' => $logs,
            'modules' => $modules,
            'actions' => $actions,
            'users' => $users,
            'branches' => $branches,
            'filters' => $request->only(['module', 'action', 'user_id', 'branch_id', 'date_from', 'date_to']),
        ]);
    }
}
