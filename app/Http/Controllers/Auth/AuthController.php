<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Services\AuditLogService;
use App\Services\AuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(
        protected AuthenticationService $authService,
        protected AuditLogService $auditLog
    ) {}

    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $this->authService->login(
            $request->validated('login'),
            $request->validated('password'),
            (bool) $request->validated('remember')
        );

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'))
            ->with('success', __('Welcome back, :name!', ['name' => Auth::user()->name]));
    }

    public function logout(): RedirectResponse
    {
        $this->authService->logout();

        return redirect()->route('login')
            ->with('info', __('You have been successfully logged out.'));
    }

    public function showProfile(): View
    {
        return view('auth.profile', [
            'user' => Auth::user(),
        ]);
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $user->password = Hash::make($request->validated('password'));
        $user->save();

        $this->auditLog->log(
            action: 'password_change',
            module: 'auth',
            description: "User {$user->name} changed their password",
            record: $user
        );

        return back()->with('success', __('Password updated successfully.'));
    }
}
