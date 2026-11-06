<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\StoreMenuItemRequest;
use App\Http\Requests\Pos\UpdateMenuItemRequest;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Services\AuditLogService;
use App\Services\BranchContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function __construct(
        protected BranchContextService $branchContext,
        protected AuditLogService $auditLog
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('permission', 'pos.menu.manage');

        $branchId = $this->branchContext->getActiveBranchId();

        $query = MenuItem::with(['category', 'modifiers'])
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)->orWhereNull('branch_id');
            });

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('department')) {
            $query->where('preparation_department', $request->input('department'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('name')->paginate(20)->withQueryString();
        $categories = MenuCategory::where(function ($q) use ($branchId) {
            $q->where('branch_id', $branchId)->orWhereNull('branch_id');
        })->orderBy('name')->get();

        return view('pos.menu.index', [
            'items' => $items,
            'categories' => $categories,
            'filters' => $request->only(['category_id', 'department', 'search']),
        ]);
    }

    public function store(StoreMenuItemRequest $request): RedirectResponse
    {
        $branchId = $this->branchContext->getActiveBranchId();

        $item = MenuItem::create([
            'branch_id' => $branchId,
            'category_id' => $request->validated('category_id'),
            'code' => $request->validated('code'),
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
            'price' => $request->validated('price'),
            'cost_price' => $request->validated('cost_price'),
            'preparation_department' => $request->validated('preparation_department'),
            'is_available' => $request->boolean('is_available', true),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->auditLog->log(
            action: 'create_menu_item',
            module: 'pos',
            description: "Added menu item: {$item->name} ({$item->preparation_department})",
            record: $item,
            branchId: $branchId
        );

        return back()->with('success', __('Menu item :name created successfully.', ['name' => $item->name]));
    }

    public function update(UpdateMenuItemRequest $request, MenuItem $menu): RedirectResponse
    {
        $oldValues = $menu->toArray();

        $menu->update([
            'category_id' => $request->validated('category_id'),
            'code' => $request->validated('code'),
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
            'price' => $request->validated('price'),
            'cost_price' => $request->validated('cost_price'),
            'preparation_department' => $request->validated('preparation_department'),
            'is_available' => $request->boolean('is_available', true),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->auditLog->log(
            action: 'update_menu_item',
            module: 'pos',
            description: "Updated menu item: {$menu->name}",
            record: $menu,
            oldValues: $oldValues,
            newValues: $menu->toArray(),
            branchId: $menu->branch_id
        );

        return back()->with('success', __('Menu item :name updated successfully.', ['name' => $menu->name]));
    }

    public function toggleAvailability(MenuItem $menu): RedirectResponse
    {
        $this->authorize('permission', 'pos.menu.manage');

        $menu->is_available = ! $menu->is_available;
        $menu->save();

        $status = $menu->is_available ? 'marked available' : 'marked sold out';

        return back()->with('success', __(':item is now :status.', ['item' => $menu->name, 'status' => $status]));
    }
}
