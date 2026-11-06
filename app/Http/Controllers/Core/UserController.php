<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Http\Requests\Core\StoreUserRequest;
use App\Http\Requests\Core\UpdateUserRequest;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        protected AuditLogService $auditLog
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('permission', 'users.view');

        $query = User::with(['branch', 'department', 'roles']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('slug', $request->input('role'));
            });
        }

        if ($request->has('status') && $request->input('status') !== '') {
            $query->where('is_active', (bool) $request->input('status'));
        }

        $users = $query->orderBy('name')->paginate(15)->withQueryString();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $roles = Role::orderBy('name')->get();

        return view('core.users.index', [
            'users' => $users,
            'branches' => $branches,
            'roles' => $roles,
            'filters' => $request->only(['search', 'branch_id', 'role', 'status']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('permission', 'users.create');

        return view('core.users.create', [
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        return DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->validated('name'),
                'username' => $request->validated('username'),
                'email' => $request->validated('email'),
                'phone' => $request->validated('phone'),
                'password' => Hash::make($request->validated('password')),
                'branch_id' => $request->validated('branch_id'),
                'department_id' => $request->validated('department_id'),
                'is_active' => $request->boolean('is_active', true),
            ]);

            $user->roles()->sync($request->validated('roles'));

            $accessibleBranches = $request->validated('accessible_branches', []);
            if (! in_array($user->branch_id, $accessibleBranches)) {
                $accessibleBranches[] = $user->branch_id;
            }
            $user->accessibleBranches()->sync($accessibleBranches);

            $this->auditLog->log(
                action: 'create',
                module: 'users',
                description: "Created user account: {$user->name} ({$user->username})",
                record: $user,
                newValues: [
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'branch_id' => $user->branch_id,
                ],
                branchId: $user->branch_id
            );

            return redirect()->route('users.index')
                ->with('success', __('User :name created successfully.', ['name' => $user->name]));
        });
    }

    public function edit(User $user): View
    {
        $this->authorize('permission', 'users.edit');

        $user->load(['roles', 'accessibleBranches']);

        return view('core.users.edit', [
            'user' => $user,
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        return DB::transaction(function () use ($request, $user) {
            $oldValues = [
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'branch_id' => $user->branch_id,
                'department_id' => $user->department_id,
                'is_active' => $user->is_active,
            ];

            $data = [
                'name' => $request->validated('name'),
                'username' => $request->validated('username'),
                'email' => $request->validated('email'),
                'phone' => $request->validated('phone'),
                'branch_id' => $request->validated('branch_id'),
                'department_id' => $request->validated('department_id'),
                'is_active' => $request->boolean('is_active', true),
            ];

            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->validated('password'));
            }

            $user->update($data);
            $user->roles()->sync($request->validated('roles'));

            $accessibleBranches = $request->validated('accessible_branches', []);
            if (! in_array($user->branch_id, $accessibleBranches)) {
                $accessibleBranches[] = $user->branch_id;
            }
            $user->accessibleBranches()->sync($accessibleBranches);

            $this->auditLog->log(
                action: 'update',
                module: 'users',
                description: "Updated user account: {$user->name}",
                record: $user,
                oldValues: $oldValues,
                newValues: $data,
                branchId: $user->branch_id
            );

            return redirect()->route('users.index')
                ->with('success', __('User :name updated successfully.', ['name' => $user->name]));
        });
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        $this->authorize('permission', 'users.edit');

        if ($user->id === Auth::id()) {
            return back()->with('error', __('You cannot deactivate your own account.'));
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        $action = $user->is_active ? 'activated' : 'deactivated';

        $this->auditLog->log(
            action: 'status_change',
            module: 'users',
            description: "User {$user->name} was {$action}",
            record: $user,
            branchId: $user->branch_id
        );

        return back()->with('success', __('User :name has been :action.', ['name' => $user->name, 'action' => $action]));
    }
}
