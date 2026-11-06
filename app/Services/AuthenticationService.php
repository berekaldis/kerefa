<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthenticationService
{
    public function __construct(
        protected AuditLogService $auditLog,
        protected BranchContextService $branchContext
    ) {}

    /**
     * Attempt login using email or username.
     *
     * @throws ValidationException
     */
    public function login(string $login, string $password, bool $remember = false): User
    {
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::with(['roles.permissions', 'branch'])
            ->where($field, $login)
            ->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => [__('The provided credentials do not match our records.')],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'login' => [__('This account has been deactivated. Please contact your system administrator.')],
            ]);
        }

        Auth::login($user, $remember);

        // Update login stats
        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ])->save();

        // Establish default branch in session
        $this->branchContext->getActiveBranchId($user);

        // Log audit
        $this->auditLog->log(
            action: 'login',
            module: 'auth',
            description: "User {$user->name} logged in successfully",
            record: $user,
            branchId: $user->branch_id
        );

        return $user;
    }

    public function logout(): void
    {
        $user = Auth::user();

        if ($user) {
            $this->auditLog->log(
                action: 'logout',
                module: 'auth',
                description: "User {$user->name} logged out",
                record: $user,
                branchId: session('kerefa_active_branch_id', $user->branch_id)
            );
        }

        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }
}
