<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Http\Requests\Core\StoreBranchRequest;
use App\Http\Requests\Core\UpdateBranchRequest;
use App\Models\Branch;
use App\Models\Department;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function __construct(
        protected AuditLogService $auditLog
    ) {}

    public function index(): View
    {
        $this->authorize('permission', 'branches.view');

        $branches = Branch::withCount(['users', 'departments'])->orderBy('name')->paginate(15);

        return view('core.branches.index', [
            'branches' => $branches,
        ]);
    }

    public function create(): View
    {
        $this->authorize('permission', 'branches.manage');

        return view('core.branches.create');
    }

    public function store(StoreBranchRequest $request): RedirectResponse
    {
        return DB::transaction(function () use ($request) {
            $branch = Branch::create([
                'code' => strtoupper($request->validated('code')),
                'name' => $request->validated('name'),
                'address' => $request->validated('address'),
                'phone' => $request->validated('phone'),
                'email' => $request->validated('email'),
                'currency' => strtoupper($request->validated('currency', 'ETB')),
                'is_main' => $request->boolean('is_main', false),
                'is_active' => $request->boolean('is_active', true),
            ]);

            // Auto-provision standard departments for new branch
            $standardDepartments = [
                ['code' => 'MGT', 'name' => 'Branch Management', 'type' => 'admin'],
                ['code' => 'KIT', 'name' => 'Kitchen', 'type' => 'kitchen'],
                ['code' => 'BAR', 'name' => 'Bar & Beverage', 'type' => 'bar'],
                ['code' => 'DIN', 'name' => 'Dining & Service', 'type' => 'dining'],
                ['code' => 'STR', 'name' => 'Branch Store', 'type' => 'inventory'],
            ];

            foreach ($standardDepartments as $dept) {
                Department::create([
                    'branch_id' => $branch->id,
                    'code' => $dept['code'],
                    'name' => $dept['name'],
                    'type' => $dept['type'],
                    'is_active' => true,
                ]);
            }

            $this->auditLog->log(
                action: 'create',
                module: 'branches',
                description: "Created new branch: {$branch->name} ({$branch->code})",
                record: $branch,
                newValues: $request->validated(),
                branchId: $branch->id
            );

            return redirect()->route('branches.index')
                ->with('success', __('Branch :name created successfully.', ['name' => $branch->name]));
        });
    }

    public function edit(Branch $branch): View
    {
        $this->authorize('permission', 'branches.manage');

        return view('core.branches.edit', [
            'branch' => $branch,
        ]);
    }

    public function update(UpdateBranchRequest $request, Branch $branch): RedirectResponse
    {
        $oldValues = $branch->toArray();

        $branch->update([
            'code' => strtoupper($request->validated('code')),
            'name' => $request->validated('name'),
            'address' => $request->validated('address'),
            'phone' => $request->validated('phone'),
            'email' => $request->validated('email'),
            'currency' => strtoupper($request->validated('currency', 'ETB')),
            'is_main' => $request->boolean('is_main', false),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->auditLog->log(
            action: 'update',
            module: 'branches',
            description: "Updated branch details: {$branch->name}",
            record: $branch,
            oldValues: $oldValues,
            newValues: $branch->toArray(),
            branchId: $branch->id
        );

        return redirect()->route('branches.index')
            ->with('success', __('Branch :name updated successfully.', ['name' => $branch->name]));
    }
}
