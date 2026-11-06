@extends('layouts.app')

@section('title', 'Orders History & Tracking')

@section('content')
<div class="container-fluid px-1 px-sm-2 px-md-3">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-success bg-opacity-10 text-success rounded-circle p-2"><i class="bi bi-receipt fs-5"></i></span>
                <div>
                    <h4 class="fw-bold mb-0 text-dark">Orders History & Tracking</h4>
                    <span class="text-muted small">Live status tracking for dine-in, takeaway & delivery orders</span>
                </div>
            </div>
        </div>
        @if(auth()->user()->hasPermission('pos.orders.create'))
            <a href="{{ route('pos.terminal') }}" class="btn btn-sm btn-kerefa rounded-pill px-3 shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> New Order (POS)
            </a>
        @endif
    </div>

    <!-- Filters Card -->
    <div class="card mb-3 border-0 shadow-sm rounded-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('orders.index') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted fw-semibold">Search Order / Guest</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 rounded-end-3" placeholder="Order #, guest name, phone..." value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>

                <div class="col-6 col-md-3">
                    <label class="form-label small text-muted fw-semibold">Order Type</label>
                    <select name="order_type" class="form-select form-select-sm rounded-3">
                        <option value="">All Types</option>
                        <option value="dine_in" {{ ($filters['order_type'] ?? '') == 'dine_in' ? 'selected' : '' }}>Dine-In</option>
                        <option value="takeaway" {{ ($filters['order_type'] ?? '') == 'takeaway' ? 'selected' : '' }}>Takeaway</option>
                        <option value="delivery" {{ ($filters['order_type'] ?? '') == 'delivery' ? 'selected' : '' }}>Delivery</option>
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted fw-semibold">Status</label>
                    <select name="status" class="form-select form-select-sm rounded-3">
                        <option value="">All Statuses</option>
                        <option value="in_preparation" {{ ($filters['status'] ?? '') == 'in_preparation' ? 'selected' : '' }}>In Preparation</option>
                        <option value="partially_ready" {{ ($filters['status'] ?? '') == 'partially_ready' ? 'selected' : '' }}>Partially Ready</option>
                        <option value="ready" {{ ($filters['status'] ?? '') == 'ready' ? 'selected' : '' }}>Ready to Serve</option>
                        <option value="served" {{ ($filters['status'] ?? '') == 'served' ? 'selected' : '' }}>Served</option>
                        <option value="completed" {{ ($filters['status'] ?? '') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ ($filters['status'] ?? '') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted fw-semibold">Server</label>
                    <select name="waiter_id" class="form-select form-select-sm rounded-3">
                        <option value="">All Staff</option>
                        @foreach($waiters as $w)
                            <option value="{{ $w->id }}" {{ ($filters['waiter_id'] ?? '') == $w->id ? 'selected' : '' }}>
                                {{ $w->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary flex-grow-1 rounded-pill shadow-sm">
                        <i class="bi bi-filter me-1"></i> Filter
                    </button>
                    <a href="{{ route('orders.index') }}" class="btn btn-sm btn-outline-secondary rounded-circle p-1" style="width: 31px; height: 31px;" title="Reset Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center border-bottom">
            <span class="fw-bold text-dark">Orders Directory ({{ $orders->total() }})</span>
            <span class="badge bg-light text-secondary border font-mono rounded-pill px-2 py-1">Live Queue</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="small text-uppercase text-muted">
                            <th class="ps-3">Order Number</th>
                            <th>Service Type</th>
                            <th>Server</th>
                            <th>Status</th>
                            <th>Department Tickets</th>
                            <th>Total</th>
                            <th>Created</th>
                            <th class="pe-3 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $ord)
                            <tr>
                                <td class="ps-3 font-mono fw-bold">
                                    <a href="{{ route('orders.show', $ord) }}" class="text-decoration-none text-dark">
                                        {{ $ord->order_number }}
                                    </a>
                                </td>
                                <td>
                                    @if($ord->order_type === 'dine_in')
                                        <span class="badge bg-light text-danger border rounded-pill px-2 py-1">
                                            <i class="bi bi-cup-hot-fill me-1"></i> Table {{ $ord->table?->table_number ?? 'N/A' }}
                                        </span>
                                    @elseif($ord->order_type === 'takeaway')
                                        <span class="badge bg-light text-primary border rounded-pill px-2 py-1">
                                            <i class="bi bi-bag-fill me-1"></i> Takeaway
                                        </span>
                                    @else
                                        <span class="badge bg-light text-success border rounded-pill px-2 py-1">
                                            <i class="bi bi-bicycle me-1"></i> Delivery
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="small text-muted">{{ $ord->waiter?->name ?? 'Staff' }}</span>
                                </td>
                                <td>
                                    @php
                                        $badgeClass = match($ord->status) {
                                            'in_preparation' => 'bg-warning-subtle text-warning border-warning-subtle',
                                            'partially_ready' => 'bg-info-subtle text-info border-info-subtle',
                                            'ready' => 'bg-success-subtle text-success border-success-subtle',
                                            'served' => 'bg-primary-subtle text-primary border-primary-subtle',
                                            'completed' => 'bg-secondary-subtle text-secondary border-secondary-subtle',
                                            'cancelled' => 'bg-danger-subtle text-danger border-danger-subtle',
                                            default => 'bg-light text-dark',
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }} border text-uppercase rounded-pill px-2 py-1" style="font-size: 0.7rem;">
                                        {{ str_replace('_', ' ', $ord->status) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($ord->tickets as $t)
                                            <span class="badge {{ $t->department === 'kitchen' ? 'bg-danger-subtle text-danger' : 'bg-info-subtle text-info' }} border rounded-pill" style="font-size: 0.68rem;">
                                                <i class="bi {{ $t->department === 'kitchen' ? 'bi-fire' : 'bi-cup-straw' }} me-1"></i>
                                                {{ ucfirst($t->department) }}: {{ strtoupper($t->status) }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="fw-bold font-mono text-dark">
                                    {{ number_format($ord->total_amount, 2) }} <small class="text-muted" style="font-size: 0.68rem;">ETB</small>
                                </td>
                                <td class="small text-muted font-mono" style="font-size: 0.75rem;">
                                    {{ $ord->created_at->diffForHumans() }}
                                </td>
                                <td class="pe-3 text-end">
                                    <a href="{{ route('orders.show', $ord) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 font-mono" style="font-size: 0.75rem;">
                                        <i class="bi bi-eye me-1"></i> Details
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">No orders found matching criteria.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($orders->hasPages())
            <div class="card-footer bg-white py-2 px-3 border-top">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
