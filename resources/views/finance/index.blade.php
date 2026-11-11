@extends('layouts.app')

@section('title', 'Finance & Accounting')

@section('content')
<div class="container-fluid px-1 px-sm-2 px-md-3">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
        <div>
            <h4 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                <i class="bi bi-journal-text text-warning"></i>
                <span>Finance & Operating Accounts</span>
            </h4>
            <span class="text-muted small">Sales revenue reconciliation, operating expenses, and cashflow tracking</span>
        </div>
        @if(auth()->user()->hasPermission('finance.expenses'))
        <div>
            <button type="button" class="btn btn-sm btn-kerefa rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#newExpenseModal">
                <i class="bi bi-plus-lg me-1"></i> Record Operating Expense
            </button>
        </div>
        @endif
    </div>

    <!-- Financial KPI Cards (Ethiopian Birr) -->
    <div class="row g-2 g-sm-3 mb-3">
        <div class="col-6 col-xl-3">
            <div class="card p-3 border-0 shadow-sm rounded-4 h-100">
                <div class="text-muted small fw-bold text-uppercase">Sales Today</div>
                <h3 class="fw-bold text-success font-mono mt-1 mb-0">ETB {{ number_format($stats['sales_today'], 2) }}</h3>
                <small class="text-muted">Settled POS orders</small>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card p-3 border-0 shadow-sm rounded-4 h-100">
                <div class="text-muted small fw-bold text-uppercase">Sales Month</div>
                <h3 class="fw-bold text-primary font-mono mt-1 mb-0">ETB {{ number_format($stats['sales_month'], 2) }}</h3>
                <small class="text-muted">Monthly gross revenue</small>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card p-3 border-0 shadow-sm rounded-4 h-100">
                <div class="text-muted small fw-bold text-uppercase">Expenses Month</div>
                <h3 class="fw-bold text-danger font-mono mt-1 mb-0">ETB {{ number_format($stats['expenses_month'], 2) }}</h3>
                <small class="text-muted">Operating expenses</small>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card p-3 border-0 shadow-sm rounded-4 h-100">
                <div class="text-muted small fw-bold text-uppercase">Net Operational Margin</div>
                <h3 class="fw-bold text-dark font-mono mt-1 mb-0">ETB {{ number_format($stats['net_profit'], 2) }}</h3>
                <small class="text-success fw-bold">Positive operating balance</small>
            </div>
        </div>
    </div>

    <!-- Expenses Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-3">
        <div class="card-header bg-white py-3 px-3 border-bottom">
            <span class="fw-bold text-dark"><i class="bi bi-wallet2 me-2 text-danger"></i>Operating Expenses Log</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="small text-uppercase text-muted">
                            <th class="ps-3">Date</th>
                            <th>Expense Title</th>
                            <th>Category</th>
                            <th>Payment Method</th>
                            <th>Recorded By</th>
                            <th class="pe-3 text-end">Amount (ETB)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expenses as $exp)
                            <tr>
                                <td class="ps-3 font-mono small text-muted">{{ $exp->date->format('Y-m-d') }}</td>
                                <td class="fw-semibold text-dark">{{ $exp->title }}</td>
                                <td>
                                    <span class="badge bg-light text-secondary border rounded-pill">{{ strtoupper($exp->category) }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary font-mono rounded-pill">{{ strtoupper($exp->payment_method) }}</span>
                                </td>
                                <td class="small">{{ $exp->recorder?->name ?? 'Finance' }}</td>
                                <td class="pe-3 text-end font-mono fw-bold text-danger fs-6">
                                    - ETB {{ number_format($exp->amount, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">No operating expenses recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- New Expense Modal -->
    <div class="modal fade" id="newExpenseModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('finance.expenses.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title fs-6 fw-bold">Record Operating Expense</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Title / Description</label>
                            <input type="text" name="title" class="form-control form-control-sm" required placeholder="e.g. Generator diesel fuel">
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Category</label>
                                <select name="category" class="form-select form-select-sm" required>
                                    <option value="utilities">Utilities & Power</option>
                                    <option value="supplies">Operating Supplies</option>
                                    <option value="maintenance">Equipment Maintenance</option>
                                    <option value="rent">Facility Rent</option>
                                    <option value="payroll">Staff Advances / Payroll</option>
                                    <option value="other">Other Operating Costs</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Payment Method</label>
                                <select name="payment_method" class="form-select form-select-sm" required>
                                    <option value="cash">Cash (Birr)</option>
                                    <option value="telebirr">Telebirr</option>
                                    <option value="cbe_birr">CBE Birr</option>
                                    <option value="card">Bank Card</option>
                                    <option value="other">Bank Transfer</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Amount (ETB)</label>
                                <input type="number" step="0.01" name="amount" class="form-control form-control-sm font-mono" required placeholder="0.00">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Expense Date</label>
                                <input type="date" name="date" class="form-control form-control-sm" required value="{{ date('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Notes</label>
                            <input type="text" name="notes" class="form-control form-control-sm" placeholder="Receipt number or details">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-primary fw-bold">Save Expense</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
