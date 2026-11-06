<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Gate;

class PermissionService
{
    /**
     * Register permission gates.
     */
    public function registerGates(): void
    {
        Gate::before(function (User $user, string $ability) {
            if ($user->isSuperAdmin()) {
                return true;
            }

            return null;
        });

        Gate::define('access-branch', function (User $user, $branch) {
            return $user->canAccessBranch($branch);
        });

        Gate::define('permission', function (User $user, string $permission) {
            return $user->hasPermission($permission);
        });
    }
}
