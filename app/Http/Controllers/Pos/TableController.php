<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\StoreTableRequest;
use App\Http\Requests\Pos\UpdateTableRequest;
use App\Models\DiningArea;
use App\Models\RestaurantTable;
use App\Services\AuditLogService;
use App\Services\BranchContextService;
use App\Services\OrderRoutingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TableController extends Controller
{
    public function __construct(
        protected BranchContextService $branchContext,
        protected OrderRoutingService $orderRouting,
        protected AuditLogService $auditLog
    ) {}

    public function index(): View
    {
        $this->authorize('permission', 'pos.tables.manage');

        $user = Auth::user();
        $branchId = $this->branchContext->getActiveBranchId($user);

        $diningAreas = DiningArea::with(['tables' => function ($q) {
            $q->with('activeOrder.waiter');
        }])
            ->where('branch_id', $branchId)
            ->orderBy('sort_order')
            ->get();

        return view('pos.tables.index', [
            'diningAreas' => $diningAreas,
        ]);
    }

    public function store(StoreTableRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $branchId = $this->branchContext->getActiveBranchId($user);

        $table = RestaurantTable::create([
            'branch_id' => $branchId,
            'dining_area_id' => $request->validated('dining_area_id'),
            'table_number' => $request->validated('table_number'),
            'capacity' => $request->validated('capacity'),
            'status' => $request->validated('status'),
            'is_active' => true,
        ]);

        $this->auditLog->log(
            action: 'create_table',
            module: 'pos',
            description: "Registered Table {$table->table_number}",
            record: $table,
            branchId: $branchId
        );

        return back()->with('success', __('Table :number registered successfully.', ['number' => $table->table_number]));
    }

    public function update(UpdateTableRequest $request, RestaurantTable $table): RedirectResponse
    {
        $oldValues = $table->toArray();

        $table->update([
            'dining_area_id' => $request->validated('dining_area_id'),
            'table_number' => $request->validated('table_number'),
            'capacity' => $request->validated('capacity'),
            'status' => $request->validated('status'),
        ]);

        $this->auditLog->log(
            action: 'update_table',
            module: 'pos',
            description: "Updated Table {$table->table_number}",
            record: $table,
            oldValues: $oldValues,
            newValues: $table->toArray(),
            branchId: $table->branch_id
        );

        return back()->with('success', __('Table :number updated successfully.', ['number' => $table->table_number]));
    }

    public function transfer(Request $request, RestaurantTable $table): RedirectResponse
    {
        $this->authorize('permission', 'pos.tables.manage');

        $request->validate([
            'target_table_id' => ['required', 'exists:restaurant_tables,id', 'different:'.$table->id],
        ]);

        $activeOrder = $table->activeOrder;
        if (! $activeOrder) {
            return back()->with('error', __('This table does not have an active order to transfer.'));
        }

        $targetTable = RestaurantTable::findOrFail($request->input('target_table_id'));
        if ($targetTable->status !== 'available') {
            return back()->with('error', __('Target table :number is not available.', ['number' => $targetTable->table_number]));
        }

        $this->orderRouting->transferTable($activeOrder, $targetTable, Auth::user());

        return back()->with('success', __('Order transferred from Table :from to Table :to successfully.', [
            'from' => $table->table_number,
            'to' => $targetTable->table_number,
        ]));
    }

    public function merge(Request $request, RestaurantTable $table): RedirectResponse
    {
        $this->authorize('permission', 'pos.tables.manage');

        $request->validate([
            'target_table_id' => ['required', 'exists:restaurant_tables,id', 'different:'.$table->id],
        ]);

        $targetTable = RestaurantTable::findOrFail($request->input('target_table_id'));

        $this->orderRouting->mergeTables($table, $targetTable, Auth::user());

        return back()->with('success', __('Table :source merged into Table :target.', [
            'source' => $table->table_number,
            'target' => $targetTable->table_number,
        ]));
    }
}
