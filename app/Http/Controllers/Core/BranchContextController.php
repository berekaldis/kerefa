<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use App\Services\BranchContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BranchContextController extends Controller
{
    public function __construct(
        protected BranchContextService $branchContext,
        protected AuditLogService $auditLog
    ) {}

    public function switchBranch(Request $request): RedirectResponse
    {
        $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
        ]);

        $user = Auth::user();
        $branchId = (int) $request->input('branch_id');

        if (! $this->branchContext->setActiveBranch($branchId, $user)) {
            return back()->with('error', __('You do not have permission to access the selected branch.'));
        }

        $branch = $this->branchContext->getActiveBranch($user);

        $this->auditLog->log(
            action: 'branch_switch',
            module: 'branches',
            description: "Switched active branch to {$branch->name}",
            record: $branch,
            branchId: $branch->id
        );

        return back()->with('success', __('Switched active branch to :branch', ['branch' => $branch->name]));
    }
}
