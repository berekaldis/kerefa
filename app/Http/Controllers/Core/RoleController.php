<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function __construct(
        protected AuditLogService $auditLog
    ) {}

    public function index(): View
    {
        $this->authorize('permission', 'roles.view');

        $roles = Role::withCount(['users', 'permissions'])->orderBy('id')->get();

        return view('core.roles.index', [
            'roles' => $roles,
        ]);
    }

    public function edit(Role $role): View
    {
        $this->authorize('permission', 'roles.manage');

        $role->load('permissions');
        $allPermissions = Permission::orderBy('module')->orderBy('name')->get()->groupBy('module');

        return view('core.roles.edit', [
            'role' => $role,
            'groupedPermissions' => $allPermissions,
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->authorize('permission', 'roles.manage');

        $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $permissionIds = $request->input('permissions', []);
        $role->permissions()->sync($permissionIds);

        $this->auditLog->log(
            action: 'update_permissions',
            module: 'roles',
            description: "Updated permissions for role: {$role->name}",
            record: $role,
            newValues: ['permissions_count' => count($permissionIds)]
        );

        return redirect()->route('roles.index')
            ->with('success', __('Permissions updated successfully for role :role.', ['role' => $role->name]));
    }
}
