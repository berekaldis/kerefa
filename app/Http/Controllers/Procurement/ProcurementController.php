<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\AuditLogService;
use App\Services\BranchContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProcurementController extends Controller
{
    public function __construct(
        protected BranchContextService $branchContext,
        protected AuditLogService $auditLog
    ) {}

    public function index(): View
    {
        $this->authorize('permission', 'procurement.view');

        $user = Auth::user();
        $branchId = $this->branchContext->getActiveBranchId($user);

        $suppliers = Supplier::withCount('purchaseOrders')->where('is_active', true)->get();

        $purchaseOrders = PurchaseOrder::with(['supplier', 'creator'])
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)->orWhereNull('branch_id');
            })
            ->latest()
            ->paginate(15);

        $stats = [
            'total_pos' => PurchaseOrder::count(),
            'total_spend' => PurchaseOrder::where('status', 'received')->sum('total_amount'),
            'suppliers_count' => Supplier::count(),
        ];

        return view('procurement.index', compact('suppliers', 'purchaseOrders', 'stats'));
    }

    public function storeOrder(Request $request): RedirectResponse
    {
        $this->authorize('permission', 'procurement.orders');

        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'total_amount' => ['required', 'numeric', 'min:1'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $user = Auth::user();
        $branchId = $this->branchContext->getActiveBranchId($user);

        $poNumber = 'PO-'.date('Ym').'-'.sprintf('%04d', PurchaseOrder::count() + 1);

        $po = PurchaseOrder::create([
            'branch_id' => $branchId,
            'supplier_id' => $validated['supplier_id'],
            'po_number' => $poNumber,
            'status' => 'pending',
            'total_amount' => $validated['total_amount'],
            'created_by' => $user->id,
            'notes' => $validated['notes'],
        ]);

        $this->auditLog->log(
            action: 'po_created',
            module: 'procurement',
            description: "Created Purchase Order {$po->po_number} (ETB {$po->total_amount})",
            record: $po,
            branchId: $branchId
        );

        return redirect()->route('procurement.index')
            ->with('success', __('Purchase Order :po created successfully.', ['po' => $poNumber]));
    }
}
