<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\StockTransaction;
use App\Services\AuditLogService;
use App\Services\BranchContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function __construct(
        protected BranchContextService $branchContext,
        protected AuditLogService $auditLog
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('permission', 'inventory.view');

        $user = Auth::user();
        $branchId = $this->branchContext->getActiveBranchId($user);

        $categories = InventoryCategory::withCount('items')->get();

        $query = InventoryItem::with('category')
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)->orWhereNull('branch_id');
            })
            ->where('is_active', true);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('search')) {
            $q = $request->input('search');
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%");
            });
        }

        $items = $query->orderBy('name')->paginate(15)->withQueryString();

        $recentTransactions = StockTransaction::with(['item', 'user'])
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)->orWhereNull('branch_id');
            })
            ->latest()
            ->take(10)
            ->get();

        $stats = [
            'total_items' => InventoryItem::count(),
            'low_stock_count' => InventoryItem::whereRaw('current_stock <= min_stock_level')->count(),
            'total_valuation' => InventoryItem::selectRaw('SUM(current_stock * unit_cost) as total')->value('total') ?? 0,
        ];

        return view('inventory.index', compact('items', 'categories', 'recentTransactions', 'stats'));
    }

    public function adjust(Request $request, InventoryItem $item): RedirectResponse
    {
        $this->authorize('permission', 'inventory.manage');

        $validated = $request->validate([
            'type' => ['required', 'in:in,out,adjustment,wastage'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $user = Auth::user();
        $qty = (float) $validated['quantity'];
        $type = $validated['type'];

        if ($type === 'in') {
            $item->current_stock += $qty;
        } else {
            $item->current_stock = max(0, $item->current_stock - $qty);
        }
        $item->save();

        StockTransaction::create([
            'branch_id' => $item->branch_id,
            'inventory_item_id' => $item->id,
            'type' => $type,
            'quantity' => $qty,
            'unit_cost' => $item->unit_cost,
            'total_cost' => $qty * $item->unit_cost,
            'user_id' => $user->id,
            'notes' => $validated['notes'] ?? 'Manual stock adjustment',
        ]);

        $this->auditLog->log(
            action: 'stock_adjusted',
            module: 'inventory',
            description: "Adjusted stock for {$item->name} ({$type}: {$qty} {$item->unit})",
            record: $item,
            branchId: $item->branch_id
        );

        return redirect()->route('inventory.index')
            ->with('success', __('Stock for :name adjusted successfully.', ['name' => $item->name]));
    }
}
