<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\KitchenBarTicket;
use App\Services\BranchContextService;
use App\Services\OrderRoutingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class KitchenDisplayController extends Controller
{
    public function __construct(
        protected BranchContextService $branchContext,
        protected OrderRoutingService $orderRouting
    ) {}

    public function index(): View
    {
        $this->authorize('permission', 'pos.kds.view');

        $user = Auth::user();
        $branchId = $this->branchContext->getActiveBranchId($user);

        // Fetch active tickets for Kitchen (queued and preparing first, then recently ready)
        $tickets = KitchenBarTicket::with(['order.table', 'order.waiter', 'items.orderItem.menuItem'])
            ->where('branch_id', $branchId)
            ->where('department', 'kitchen')
            ->whereIn('status', ['queued', 'preparing', 'ready'])
            ->orderByRaw("CASE status WHEN 'preparing' THEN 1 WHEN 'queued' THEN 2 WHEN 'ready' THEN 3 ELSE 4 END")
            ->orderBy('sent_at', 'asc')
            ->get();

        return view('pos.kds.index', [
            'tickets' => $tickets,
        ]);
    }

    public function updateStatus(Request $request, KitchenBarTicket $ticket): RedirectResponse
    {
        $this->authorize('permission', 'pos.kds.view');

        $request->validate([
            'status' => ['required', 'in:queued,preparing,ready,collected'],
        ]);

        $this->orderRouting->updateTicketStatus(
            $ticket,
            $request->input('status'),
            Auth::user()
        );

        return back()->with('success', __('Kitchen ticket :number marked as :status.', [
            'number' => $ticket->ticket_number,
            'status' => ucfirst($request->input('status')),
        ]));
    }
}
