<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\CreateOrderRequest;
use App\Models\DiningArea;
use App\Models\MenuCategory;
use App\Services\BranchContextService;
use App\Services\OrderRoutingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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
                ->with('modifiers');
        }])
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)->orWhereNull('branch_id');
            })
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        // Fetch dining areas with tables
        $diningAreas = DiningArea::with(['tables' => function ($q) {
            $q->where('is_active', true)->with('activeOrder');
        }])
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('pos.terminal', [
            'branch' => $branch,
            'categories' => $categories,
            'diningAreas' => $diningAreas,
        ]);
    }

    public function storeOrder(CreateOrderRequest $request): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        $branchId = $this->branchContext->getActiveBranchId($user);

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
            $user
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
}
