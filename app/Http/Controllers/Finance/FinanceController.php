<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Order;
use App\Services\AuditLogService;
use App\Services\BranchContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function __construct(
        protected BranchContextService $branchContext,
        protected AuditLogService $auditLog
    ) {}

    public function index(): View
    {
        $this->authorize('permission', 'finance.view');

        $user = Auth::user();
        $branchId = $this->branchContext->getActiveBranchId($user);

        $expenses = Expense::with('recorder')
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)->orWhereNull('branch_id');
            })
            ->latest('date')
            ->paginate(15);

        // Sales Revenue from settled POS Orders
        $salesToday = Order::where(function ($q) use ($branchId) {
            $q->where('branch_id', $branchId)->orWhereNull('branch_id');
        })
            ->whereDate('created_at', today())
            ->where('status', 'completed')
            ->sum('total_amount');

        $salesMonth = Order::where(function ($q) use ($branchId) {
            $q->where('branch_id', $branchId)->orWhereNull('branch_id');
        })
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('status', 'completed')
            ->sum('total_amount');

        $expensesMonth = Expense::where(function ($q) use ($branchId) {
            $q->where('branch_id', $branchId)->orWhereNull('branch_id');
        })
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('amount');

        $stats = [
            'sales_today' => $salesToday,
            'sales_month' => $salesMonth,
            'expenses_month' => $expensesMonth,
            'net_profit' => $salesMonth - $expensesMonth,
        ];

        return view('finance.index', compact('expenses', 'stats'));
    }

    public function storeExpense(Request $request): RedirectResponse
    {
        $this->authorize('permission', 'finance.expenses');

        $validated = $request->validate([
            'category' => ['required', 'string', 'max:60'],
            'title' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'string', 'in:cash,telebirr,cbe_birr,card,other'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $user = Auth::user();
        $branchId = $this->branchContext->getActiveBranchId($user);

        $expense = Expense::create(array_merge($validated, [
            'branch_id' => $branchId,
            'recorded_by' => $user->id,
        ]));

        $this->auditLog->log(
            action: 'expense_recorded',
            module: 'finance',
            description: "Recorded expense: {$expense->title} (ETB {$expense->amount})",
            record: $expense,
            branchId: $branchId
        );

        return redirect()->route('finance.index')
            ->with('success', __('Expense recorded successfully.'));
    }
}
