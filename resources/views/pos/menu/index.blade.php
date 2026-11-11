@extends('layouts.app')

@section('title', 'Menu & Product Management')

@section('content')
<div class="container-fluid px-1 px-sm-2 px-md-3">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-warning bg-opacity-10 text-warning rounded-circle p-2"><i class="bi bi-card-list fs-5"></i></span>
                <div>
                    <h4 class="fw-bold mb-0 text-dark">Menu & Product Directory</h4>
                    <span class="text-muted small">Configure food, drinks, pricing, kitchen/bar routing & 86'd status</span>
                </div>
            </div>
        </div>
        @if(auth()->user()->hasPermission('pos.menu.manage'))
        <button type="button" class="btn btn-sm btn-kerefa rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addMenuItemModal">
            <i class="bi bi-plus-lg me-1"></i> Add Product
        </button>
        @endif
    </div>

    <!-- Filters Card -->
    <div class="card mb-3 border-0 shadow-sm rounded-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('menu.index') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label small text-muted fw-semibold">Search Product or Code</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 rounded-end-3" placeholder="Search by name, code..." value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>

                <div class="col-6 col-md-3">
                    <label class="form-label small text-muted fw-semibold">Category</label>
                    <select name="category_id" class="form-select form-select-sm rounded-3">
                        <option value="">All Categories</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}" {{ ($filters['category_id'] ?? '') == $c->id ? 'selected' : '' }}>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-3">
                    <label class="form-label small text-muted fw-semibold">Preparation Routing</label>
                    <select name="department" class="form-select form-select-sm rounded-3">
                        <option value="">All Stations</option>
                        <option value="kitchen" {{ ($filters['department'] ?? '') == 'kitchen' ? 'selected' : '' }}>Kitchen (Food)</option>
                        <option value="bar" {{ ($filters['department'] ?? '') == 'bar' ? 'selected' : '' }}>Bar (Beverages & Coffee)</option>
                    </select>
                </div>

                <div class="col-12 col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary flex-grow-1 rounded-pill shadow-sm">
                        <i class="bi bi-filter me-1"></i> Filter
                    </button>
                    <a href="{{ route('menu.index') }}" class="btn btn-sm btn-outline-secondary rounded-circle p-1" style="width: 31px; height: 31px;" title="Reset Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Menu Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center border-bottom">
            <span class="fw-bold text-dark">Menu Catalog ({{ $items->total() }})</span>
            <span class="badge bg-light text-secondary border font-mono rounded-pill px-2 py-1">Active Catalog</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="small text-uppercase text-muted">
                            <th class="ps-3">Code</th>
                            <th>Product Name & Recipe Notes</th>
                            <th>Category</th>
                            <th>Station</th>
                            <th class="text-end">Price</th>
                            <th class="text-end">Cost</th>
                            <th>Availability</th>
                            @if(auth()->user()->hasPermission('pos.menu.manage'))
                            <th class="pe-3 text-end">Action</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td class="ps-3 font-mono fw-bold text-muted small">{{ $item->code ?? '-' }}</td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $item->name }}</div>
                                    <small class="text-muted d-block" style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 0.75rem;">{{ $item->description }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border rounded-pill px-2 py-1">{{ $item->category?->name }}</span>
                                </td>
                                <td>
                                    <span class="badge {{ $item->preparation_department === 'bar' ? 'bg-info bg-opacity-25 text-info' : 'bg-danger bg-opacity-25 text-danger' }} rounded-pill" style="font-size: 0.7rem;">
                                        <i class="bi {{ $item->preparation_department === 'bar' ? 'bi-cup-straw' : 'bi-fire' }} me-1"></i>
                                        {{ ucfirst($item->preparation_department) }}
                                    </span>
                                </td>
                                <td class="text-end font-mono fw-bold text-dark">
                                    {{ number_format($item->price, 2) }} <small class="text-muted" style="font-size: 0.68rem;">ETB</small>
                                </td>
                                <td class="text-end font-mono text-muted small">
                                    {{ $item->cost_price ? number_format($item->cost_price, 2) . ' ETB' : '-' }}
                                </td>
                                <td>
                                    @if($item->is_available)
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2 py-1">Available</span>
                                    @else
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-2 py-1">Sold Out (86'd)</span>
                                    @endif
                                </td>
                                @if(auth()->user()->hasPermission('pos.menu.manage'))
                                <td class="pe-3 text-end">
                                    <div class="btn-group btn-group-sm">
                                        <form method="POST" action="{{ route('menu.toggle-availability', $item) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm {{ $item->is_available ? 'btn-outline-warning' : 'btn-outline-success' }} rounded-start-pill py-1 px-2" title="{{ $item->is_available ? 'Mark Sold Out' : 'Mark Available' }}">
                                                <i class="bi {{ $item->is_available ? 'bi-slash-circle' : 'bi-check-circle' }}"></i>
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-end-pill py-1 px-2" data-bs-toggle="modal" data-bs-target="#editItemModal{{ $item->id }}" title="Edit Product">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                    </div>
                                </td>
                                @endif
                            </tr>

                            <!-- Edit Menu Item Modal -->
                            <div class="modal fade" id="editItemModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow-lg rounded-4">
                                        <form method="POST" action="{{ route('menu.update', $item) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-header">
                                                <h6 class="modal-title fw-bold">Edit Product: {{ $item->name }}</h6>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body text-start">
                                                <div class="mb-3">
                                                    <label class="form-label small fw-semibold">Item Name <span class="text-danger">*</span></label>
                                                    <input type="text" name="name" class="form-control rounded-3" value="{{ $item->name }}" required>
                                                </div>
                                                <div class="row g-2 mb-3">
                                                    <div class="col-6">
                                                        <label class="form-label small fw-semibold">Item Code</label>
                                                        <input type="text" name="code" class="form-control rounded-3" value="{{ $item->code }}">
                                                    </div>
                                                    <div class="col-6">
                                                        <label class="form-label small fw-semibold">Category <span class="text-danger">*</span></label>
                                                        <select name="category_id" class="form-select rounded-3" required>
                                                            @foreach($categories as $c)
                                                                <option value="{{ $c->id }}" {{ $item->category_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="row g-2 mb-3">
                                                    <div class="col-6">
                                                        <label class="form-label small fw-semibold">Selling Price (ETB) <span class="text-danger">*</span></label>
                                                        <input type="number" step="0.01" name="price" class="form-control rounded-3" value="{{ $item->price }}" required>
                                                    </div>
                                                    <div class="col-6">
                                                        <label class="form-label small fw-semibold">Cost Price (ETB)</label>
                                                        <input type="number" step="0.01" name="cost_price" class="form-control rounded-3" value="{{ $item->cost_price }}">
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-semibold">Preparation Routing Department <span class="text-danger">*</span></label>
                                                    <select name="preparation_department" class="form-select rounded-3" required>
                                                        <option value="kitchen" {{ $item->preparation_department === 'kitchen' ? 'selected' : '' }}>Kitchen (Food Dispatch)</option>
                                                        <option value="bar" {{ $item->preparation_department === 'bar' ? 'selected' : '' }}>Bar (Beverage Dispatch)</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-semibold">Description</label>
                                                    <textarea name="description" class="form-control rounded-3" rows="2">{{ $item->description }}</textarea>
                                                </div>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="is_available" value="1" id="avail_{{ $item->id }}" {{ $item->is_available ? 'checked' : '' }}>
                                                    <label class="form-check-label small" for="avail_{{ $item->id }}">In Stock & Available on POS</label>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary rounded-pill px-3">Update Product</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">No menu products found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($items->hasPages())
            <div class="card-footer bg-white py-2 px-3 border-top">
                {{ $items->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Add Menu Item Modal -->
<div class="modal fade" id="addMenuItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form method="POST" action="{{ route('menu.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add Menu Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Item Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control rounded-3" placeholder="e.g. Special Tibs" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Item Code</label>
                            <input type="text" name="code" class="form-control rounded-3" placeholder="e.g. TIB-01">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Category <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select rounded-3" required>
                                <option value="">Select Category...</option>
                                @foreach($categories as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Selling Price (ETB) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="price" class="form-control rounded-3" placeholder="0.00" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Cost Price (ETB)</label>
                            <input type="number" step="0.01" name="cost_price" class="form-control rounded-3" placeholder="0.00">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Preparation Routing Department <span class="text-danger">*</span></label>
                        <select name="preparation_department" class="form-select rounded-3" required>
                            <option value="kitchen">Kitchen (Food Dispatch)</option>
                            <option value="bar">Bar (Beverage Dispatch)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Description</label>
                        <textarea name="description" class="form-control rounded-3" rows="2" placeholder="Ingredients, portion size..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-kerefa rounded-pill px-4">Create Product</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
