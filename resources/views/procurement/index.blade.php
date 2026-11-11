@extends('layouts.app')

@section('title', 'Procurement & Suppliers')

@section('content')
<div class="container-fluid px-1 px-sm-2 px-md-3">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
        <div>
            <h4 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                <i class="bi bi-truck text-warning"></i>
                <span>Procurement & Supplier Management</span>
            </h4>
            <span class="text-muted small">Vendor directory, purchase orders, and goods receiving</span>
        </div>
        @if(auth()->user()->hasPermission('procurement.orders'))
        <div>
            <button type="button" class="btn btn-sm btn-kerefa rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#newPoModal">
                <i class="bi bi-plus-lg me-1"></i> Create Purchase Order
            </button>
        </div>
        @endif
    </div>

    <!-- Stats -->
    <div class="row g-2 g-sm-3 mb-3">
        <div class="col-6 col-md-4">
            <div class="card p-3 border-0 shadow-sm rounded-4 h-100">
                <div class="text-muted small fw-bold text-uppercase">Total Purchase Orders</div>
                <h3 class="fw-bold text-dark font-mono mt-1 mb-0">{{ $stats['total_pos'] }}</h3>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card p-3 border-0 shadow-sm rounded-4 h-100">
                <div class="text-muted small fw-bold text-uppercase">Active Suppliers</div>
                <h3 class="fw-bold text-primary font-mono mt-1 mb-0">{{ $stats['suppliers_count'] }}</h3>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card p-3 border-0 shadow-sm rounded-4 h-100">
                <div class="text-muted small fw-bold text-uppercase">Procurement Spend</div>
                <h3 class="fw-bold text-success font-mono mt-1 mb-0">ETB {{ number_format($stats['total_spend'], 2) }}</h3>
            </div>
        </div>
    </div>

    <!-- Purchase Orders Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-3">
        <div class="card-header bg-white py-3 px-3 border-bottom">
            <span class="fw-bold text-dark"><i class="bi bi-receipt-cutoff me-2 text-primary"></i>Purchase Orders Log</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="small text-uppercase text-muted">
                            <th class="ps-3">PO Number</th>
                            <th>Supplier</th>
                            <th>Created By</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th class="pe-3 text-end">Total (ETB)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchaseOrders as $po)
                            <tr>
                                <td class="ps-3 font-mono small fw-bold text-primary">{{ $po->po_number }}</td>
                                <td class="fw-semibold text-dark">{{ $po->supplier?->name }}</td>
                                <td>{{ $po->creator?->name }}</td>
                                <td>
                                    <span class="badge {{ $po->status === 'received' ? 'bg-success' : 'bg-warning text-dark' }} rounded-pill">
                                        {{ strtoupper($po->status) }}
                                    </span>
                                </td>
                                <td class="small font-mono">{{ $po->created_at->format('Y-m-d H:i') }}</td>
                                <td class="pe-3 text-end font-mono fw-bold text-dark">ETB {{ number_format($po->total_amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">No purchase orders found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Supplier Directory -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-3">
        <div class="card-header bg-white py-3 px-3 border-bottom">
            <span class="fw-bold text-dark"><i class="bi bi-building me-2 text-warning"></i>Active Supplier Directory</span>
        </div>
        <div class="card-body p-3">
            <div class="row g-3">
                @foreach($suppliers as $sup)
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="p-3 border rounded-3 bg-light h-100">
                            <div class="fw-bold text-dark fs-6 mb-1">{{ $sup->name }}</div>
                            <div class="text-muted small mb-2"><i class="bi bi-person me-1"></i>{{ $sup->contact_person }}</div>
                            <div class="text-muted small"><i class="bi bi-telephone me-1"></i>{{ $sup->phone }}</div>
                            <div class="text-muted small"><i class="bi bi-geo-alt me-1"></i>{{ $sup->address }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Create PO Modal -->
    <div class="modal fade" id="newPoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('procurement.orders.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title fs-6 fw-bold">Create New Purchase Order</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Select Supplier</label>
                            <select name="supplier_id" class="form-select form-select-sm" required>
                                @foreach($suppliers as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Total Amount (ETB)</label>
                            <input type="number" step="0.01" name="total_amount" class="form-control form-control-sm font-mono" required placeholder="0.00">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Purchase Order Description / Notes</label>
                            <textarea name="notes" class="form-control form-control-sm" rows="3" placeholder="Items, delivery dates, or instructions..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-primary fw-bold">Issue Purchase Order</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
