<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\CreateOrderRequest;
use App\Models\DiningArea;
use App\Models\MenuCategory;
use App\Models\Order;
use App\Models\User;
use App\Services\BranchContextService;
use App\Services\OrderRoutingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PosController extends Controller
{
    public function __construct(
        protected BranchContextService $branchContext,
        protected OrderRoutingService $orderRouting
    ) {}

    public function terminal(): View
    {
        $this->authorize('permission', 'pos.view');

        $user = Auth::user();
        $branchId = $this->branchContext->getActiveBranchId($user);
        $branch = $this->branchContext->getActiveBranch($user);

        // Fetch categories with active menu items
        $categories = MenuCategory::with(['menuItems' => function ($q) use ($branchId) {
            $q->where(function ($query) use ($branchId) {
                $query->where('branch_id', $branchId)->orWhereNull('branch_id');
            })
                ->where('is_active', true)
                ->where('is_available', true)
                ->with(['modifiers', 'orderStation']);
        }, 'orderStation'])
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)->orWhereNull('branch_id');
            })
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        // Fetch dining areas with tables and active orders for instant retrieval
        $diningAreas = DiningArea::with(['tables' => function ($q) {
            $q->where('is_active', true)->with(['activeOrder.waiter', 'activeOrder.items.menuItem', 'activeOrder.tickets.station']);
        }])
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        // Fetch recent orders
        $recentOrders = Order::with(['table.diningArea', 'waiter', 'cashier', 'items.menuItem', 'tickets.station'])
            ->where('branch_id', $branchId)
            ->latest('id')
            ->take(50)
            ->get();

        // Fetch active waiters from HR module Waiter/Dining department
        $waiters = User::where('is_active', true)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)->orWhereNull('branch_id');
            })
            ->where(function ($q) {
                $q->whereHas('department', function ($dq) {
                    $dq->where('type', 'dining')
                        ->orWhere('code', 'DIN')
                        ->orWhere('code', 'WTR')
                        ->orWhere('name', 'like', '%waiter%')
                        ->orWhere('name', 'like', '%dining%');
                })->orWhereHas('roles', function ($rq) {
                    $rq->whereIn('slug', ['waiter', 'server', 'cashier']);
                });
            })
            ->orderBy('name')
            ->get();

        // Fallback to active branch staff if HR department not yet populated
        if ($waiters->isEmpty()) {
            $waiters = User::where('is_active', true)
                ->where(function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId)->orWhereNull('branch_id');
                })
                ->orderBy('name')
                ->get();
        }

        return view('pos.terminal', [
            'branch' => $branch,
            'categories' => $categories,
            'diningAreas' => $diningAreas,
            'recentOrders' => $recentOrders,
            'waiters' => $waiters,
        ]);
    }

    public function storeOrder(CreateOrderRequest $request): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        $branchId = $this->branchContext->getActiveBranchId($user);

        $waiter = $request->filled('waiter_id')
            ? (User::find($request->validated('waiter_id')) ?? $user)
            : $user;

        $orderData = [
            'branch_id' => $branchId,
            'order_type' => $request->validated('order_type'),
            'restaurant_table_id' => $request->validated('restaurant_table_id'),
            'customer_name' => $request->validated('customer_name'),
            'customer_phone' => $request->validated('customer_phone'),
            'delivery_address' => $request->validated('delivery_address'),
            'notes' => $request->validated('notes'),
        ];

        $order = $this->orderRouting->createOrder(
            $orderData,
            $request->validated('items'),
            $waiter
        );

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Order :number created and routed successfully.', ['number' => $order->order_number]),
                'order' => $order->load(['items.menuItem', 'tickets.items.orderItem.menuItem', 'tickets.station', 'table.diningArea', 'waiter']),
            ]);
        }

        return redirect()->route('orders.show', $order)
            ->with('success', __('Order :number created and routed to preparation departments.', ['number' => $order->order_number]));
    }

    /**
     * Add new items to an existing held order and print supplementary tickets.
     */
    public function addItemsToOrder(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_item_id' => ['required', 'exists:menu_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
            'items.*.modifiers' => ['nullable', 'array'],
        ]);

        $user = Auth::user();
        $result = $this->orderRouting->addItemsToExistingOrder(
            $order,
            $validated['items'],
            $user
        );

        return response()->json([
            'success' => true,
            'message' => __('New items added to Order :number successfully.', ['number' => $order->order_number]),
            'order' => $result['order'],
            'new_tickets' => $result['new_tickets'],
        ]);
    }

    /**
     * Settle / Mark an order as Paid in Ethiopian Birr and free the dining table.
     */
    public function settleOrder(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'payment_method' => ['required', 'string', 'in:cash,telebirr,cbe_birr,card,room_charge,other'],
            'amount_tendered' => ['nullable', 'numeric', 'min:0'],
        ]);

        $cashier = Auth::user();
        $amountTendered = (float) ($validated['amount_tendered'] ?? $order->total_amount);

        $settledOrder = $this->orderRouting->settleOrder(
            $order,
            $validated['payment_method'],
            $amountTendered,
            $cashier
        );

        return response()->json([
            'success' => true,
            'message' => __('Order :number settled and marked as Paid.', ['number' => $settledOrder->order_number]),
            'order' => $settledOrder,
        ]);
    }
}
