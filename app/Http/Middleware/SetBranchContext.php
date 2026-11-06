<?php

namespace App\Http\Middleware;

use App\Services\BranchContextService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetBranchContext
{
    public function __construct(
        protected BranchContextService $branchContext
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            $activeBranchId = $this->branchContext->getActiveBranchId($user);
            $activeBranch = $this->branchContext->getActiveBranch($user);
            $accessibleBranches = $this->branchContext->getAccessibleBranches($user);

            View::share('currentBranch', $activeBranch);
            View::share('currentBranchId', $activeBranchId);
            View::share('accessibleBranches', $accessibleBranches);
        }

        return $next($request);
    }
}
