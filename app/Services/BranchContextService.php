<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class BranchContextService
{
    public const SESSION_KEY = 'kerefa_active_branch_id';

    public function getActiveBranchId(?User $user = null): ?int
    {
        $sessionBranchId = session(self::SESSION_KEY);
        if ($sessionBranchId) {
            return (int) $sessionBranchId;
        }

        if ($user) {
            $branchId = $user->branch_id ?? Branch::where('is_active', true)->where('is_main', true)->value('id');
            if ($branchId) {
                session([self::SESSION_KEY => $branchId]);

                return (int) $branchId;
            }
        }

        return null;
    }

    public function getActiveBranch(?User $user = null): ?Branch
    {
        $branchId = $this->getActiveBranchId($user);

        return $branchId ? Branch::find($branchId) : null;
    }

    public function setActiveBranch(int $branchId, User $user): bool
    {
        if (! $user->canAccessBranch($branchId)) {
            return false;
        }

        $branch = Branch::where('id', $branchId)->where('is_active', true)->first();
        if (! $branch) {
            return false;
        }

        session([self::SESSION_KEY => $branch->id]);

        return true;
    }

    /**
     * @return Collection<int, Branch>
     */
    public function getAccessibleBranches(User $user): Collection
    {
        if ($user->isSuperAdmin()) {
            return Branch::where('is_active', true)->orderBy('name')->get();
        }

        $branchIds = collect([$user->branch_id])
            ->merge($user->accessibleBranches()->pluck('branches.id'))
            ->filter()
            ->unique();

        return Branch::whereIn('id', $branchIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
