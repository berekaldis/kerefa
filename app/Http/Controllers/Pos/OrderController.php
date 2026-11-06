<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\CancelOrderRequest;
use App\Models\Order;
use App\Models\User;
use App\Services\BranchContextService;
use App\Services\OrderRoutingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(
        protected BranchContextService $branchContext,
        protected OrderRoutingService $orderRouting
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('permission', 'pos.view');

        $user = Auth::user();
        $branchId = $this->branchContext->getActiveBranchId($user);

        $query = Order::with(['table.diningArea', 'waiter', 'tickets'])
            ->where('branch_id', $branchId);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('order_type')) {
            $query->where('order_type', $request->input('order_type'));
        }

        if ($request->filled('waiter_id')) {
            $query->where('waiter_id', $request->input('waiter_id'));
        }

        $orders = $query->latest('id')->paginate(15)->withQueryString();
        $waiters = User::where('branch_id', $branchId)->orderBy('name')->get();

        return view('pos.orders.index', [
            'orders' => $orders,
            'waiters' => $waiters,
            'filters' => $request->only(['search', 'status', 'order_type', 'waiter_id']),
        ]);
    }

    public function show(Order $order): View
    {
        $this->authorize('permission', 'pos.view');

        $order->load([
            'table.diningArea',
            'waiter',
            'cancelledBy',
            'items.menuItem',
            'items.modifiers',
            'tickets.items.orderItem.menuItem',
            'statusHistory.user',
        ]);

        return view('pos.orders.show', [
            'order' => $order,
        ]);
    }

    public function cancel(CancelOrderRequest $request, Order $order): RedirectResponse
    {
        $user = Auth::user();

        if (! $order->isOpen()) {
            return back()->with('error', __('This order cannot be cancelled because it is already :status.', ['status' => $order->status]));
        }

        $this->orderRouting->cancelOrder(
            $order,
            $user,
            $request->validated('reason')
        );

        return back()->with('success', __('Order :number has been cancelled.', ['number' => $order->order_number]));
    }
}
