@extends('layouts.app')

@section('title', 'Stock & Inventory Management')

@section('content')
<div class="container-fluid px-1 px-sm-2 px-md-3">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
        <div>
            <h4 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                <i class="bi bi-box-seam text-warning"></i>
                <span>Stock & Inventory Management</span>
            </h4>
            <span class="text-muted small">Multi-category stock balances, reorder thresholds, and immutable movement ledger</span>
        </div>
        <div>
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-2">
                Valuation: <strong class="font-mono">ETB {{ number_format($stats['total_valuation'], 2) }}</strong>
            </span>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row g-2 g-sm-3 mb-3">
        <div class="col-6 col-md-4">
            <div class="card p-3 border-0 shadow-sm rounded-4 h-100">
                <div class="text-muted small fw-bold text-uppercase">Total Items</div>
                <h3 class="fw-bold text-dark font-mono mt-1 mb-0">{{ $stats['total_items'] }}</h3>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card p-3 border-0 shadow-sm rounded-4 h-100">
                <div class="text-muted small fw-bold text-uppercase">Low Stock Alerts</div>
                <h3 class="fw-bold text-danger font-mono mt-1 mb-0">{{ $stats['low_stock_count'] }}</h3>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card p-3 border-0 shadow-sm rounded-4 h-100">
                <div class="text-muted small fw-bold text-uppercase">Categories</div>
                <h3 class="fw-bold text-primary font-mono mt-1 mb-0">{{ $categories->count() }}</h3>
            </div>
        </div>
    </div>

    <!-- Main Inventory Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-3">
        <div class="card-header bg-white py-3 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom">
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('inventory.index') }}" class="btn btn-sm {{ !request('category_id') ? 'btn-primary' : 'btn-outline-secondary' }} rounded-pill">All</a>
                @foreach($categories as $cat)
                    <a href="{{ route('inventory.index', ['category_id' => $cat->id]) }}" class="btn btn-sm {{ request('category_id') == $cat->id ? 'btn-primary' : 'btn-outline-secondary' }} rounded-pill">
                        {{ $cat->name }} ({{ $cat->items_count }})
                    </a>
                @endforeach
            </div>

            <form method="GET" action="{{ route('inventory.index') }}" class="d-flex gap-1">
                @if(request('category_id'))
                    <input type="hidden" name="category_id" value="{{ request('category_id') }}">
                @endif
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search item or SKU..." class="form-control form-control-sm rounded-pill">
                <button type="submit" class="btn btn-sm btn-dark rounded-pill px-3">Search</button>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="small text-uppercase text-muted">
                            <th class="ps-3">Code / SKU</th>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th class="text-center">Unit</th>
                            <th class="text-end">Current Stock</th>
                            <th class="text-end">Min Threshold</th>
                            <th class="text-end">Unit Cost</th>
                            <th class="text-end">Stock Value</th>
                            <th class="text-center">Status</th>
                            @if(auth()->user()->hasPermission('inventory.manage'))
                            <th class="pe-3 text-end">Action</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            @php
                                $isLow = $item->current_stock <= $item->min_stock_level;
                            @endphp
                            <tr>
                                <td class="ps-3 font-mono small fw-bold text-primary">{{ $item->code }}</td>
                                <td class="fw-semibold text-dark">{{ $item->name }}</td>
                                <td><span class="badge bg-light text-secondary border rounded-pill">{{ $item->category?->name }}</span></td>
                                <td class="text-center font-mono small">{{ $item->unit }}</td>
                                <td class="text-end font-mono fw-bold fs-6 {{ $isLow ? 'text-danger' : 'text-dark' }}">
                                    {{ number_format($item->current_stock, 2) }}
                                </td>
                                <td class="text-end font-mono small text-muted">{{ number_format($item->min_stock_level, 2) }}</td>
                                <td class="text-end font-mono small">ETB {{ number_format($item->unit_cost, 2) }}</td>
                                <td class="text-end font-mono fw-bold text-dark">ETB {{ number_format($item->current_stock * $item->unit_cost, 2) }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $isLow ? 'bg-danger' : 'bg-success' }} rounded-pill">
                                        {{ $isLow ? 'LOW STOCK' : 'IN STOCK' }}
                                    </span>
                                </td>
                                @if(auth()->user()->hasPermission('inventory.manage'))
                                <td class="pe-3 text-end">
                                    <button type="button" class="btn btn-xs btn-outline-dark rounded-pill px-2.5" data-bs-toggle="modal" data-bs-target="#adjustModal{{ $item->id }}">
                                        Adjust
                                    </button>

                                    <!-- Adjust Modal -->
                                    <div class="modal fade text-start" id="adjustModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <form method="POST" action="{{ route('inventory.adjust', $item) }}">
                                                    @csrf
                                                    <div class="modal-header">
                                                        <h5 class="modal-title fs-6">Adjust Stock: {{ $item->name }}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-bold">Adjustment Type</label>
                                                            <select name="type" class="form-select form-select-sm" required>
                                                                <option value="in">Stock In (Receive Delivery / Return)</option>
                                                                <option value="out">Stock Out (Usage / Kitchen Issue)</option>
                                                                <option value="wastage">Wastage / Spoilage</option>
                                                                <option value="adjustment">Physical Audit Adjustment</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-bold">Quantity ({{ $item->unit }})</label>
                                                            <input type="number" step="0.01" name="quantity" class="form-control form-control-sm" required placeholder="0.00">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-bold">Reason / Notes</label>
                                                            <input type="text" name="notes" class="form-control form-control-sm" placeholder="Reason for adjustment">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-sm btn-primary fw-bold">Confirm Adjustment</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">No inventory items found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-3 border-top">
                {{ $items->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
