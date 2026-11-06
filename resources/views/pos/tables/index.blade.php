@extends('layouts.app')

@section('title', 'Table Management & Floor Plan')

@section('content')
<div class="container-fluid px-1 px-sm-2 px-md-3">
    <!-- Header with Live Counters and Action -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary bg-opacity-10 text-primary rounded-circle p-2"><i class="bi bi-grid-3x3 fs-5"></i></span>
                <div>
                    <h4 class="fw-bold mb-0 text-dark">Restaurant Floor Plan & Tables</h4>
                    <span class="text-muted small">Live table seating, occupancy, table transfer & merging</span>
                </div>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-kerefa rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addTableModal">
                <i class="bi bi-plus-lg me-1"></i> Add Table
            </button>
        </div>
    </div>

    <!-- Live Status KPI Summary Bar -->
    @php
        $allTables = $diningAreas->flatMap->tables;
        $totalTables = $allTables->count();
        $availCount = $allTables->where('status', 'available')->count();
        $occCount = $allTables->where('status', 'occupied')->count();
        $resCount = $allTables->where('status', 'reserved')->count();
    @endphp
    <div class="row g-2 mb-3">
        <div class="col-6 col-sm-3">
            <div class="card p-2 border-0 shadow-sm rounded-3 d-flex flex-row align-items-center gap-3">
                <div class="rounded-circle bg-dark text-white p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                    <i class="bi bi-grid-3x3"></i>
                </div>
                <div>
                    <div class="text-muted small" style="font-size: 0.72rem;">Total Tables</div>
                    <div class="fw-bold font-mono fs-5">{{ $totalTables }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-3">
            <div class="card p-2 border-0 shadow-sm rounded-3 d-flex flex-row align-items-center gap-3">
                <div class="rounded-circle bg-success bg-opacity-10 text-success p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div>
                    <div class="text-muted small" style="font-size: 0.72rem;">Available</div>
                    <div class="fw-bold font-mono fs-5 text-success">{{ $availCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-3">
            <div class="card p-2 border-0 shadow-sm rounded-3 d-flex flex-row align-items-center gap-3">
                <div class="rounded-circle bg-danger bg-opacity-10 text-danger p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                    <i class="bi bi-person-fill"></i>
                </div>
                <div>
                    <div class="text-muted small" style="font-size: 0.72rem;">Occupied</div>
                    <div class="fw-bold font-mono fs-5 text-danger">{{ $occCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-3">
            <div class="card p-2 border-0 shadow-sm rounded-3 d-flex flex-row align-items-center gap-3">
                <div class="rounded-circle bg-info bg-opacity-10 text-info p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                    <i class="bi bi-bookmark-fill"></i>
                </div>
                <div>
                    <div class="text-muted small" style="font-size: 0.72rem;">Reserved</div>
                    <div class="fw-bold font-mono fs-5 text-info">{{ $resCount }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dining Areas Tabs / Cards -->
    @foreach($diningAreas as $area)
        <div class="card mb-3 shadow-sm border rounded-4 overflow-hidden">
            <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-geo-alt-fill text-danger"></i> {{ $area->name }}
                    </h6>
                    <small class="text-muted" style="font-size: 0.75rem;">{{ $area->description }}</small>
                </div>
                <span class="badge bg-light text-secondary border rounded-pill font-mono px-2 py-1">{{ count($area->tables) }} Tables</span>
            </div>
            <div class="card-body p-3">
                <div class="row g-2 g-sm-3">
                    @forelse($area->tables as $table)
                        @php
                            $cardBorder = match($table->status) {
                                'available' => 'border-success border-2 bg-success bg-opacity-10',
                                'occupied' => 'border-danger border-2 bg-danger bg-opacity-10',
                                'reserved' => 'border-info border-2 bg-info bg-opacity-10',
                                'maintenance' => 'border-secondary',
                                default => 'border-light',
                            };
                            $statusBadge = match($table->status) {
                                'available' => 'bg-success text-white',
                                'occupied' => 'bg-danger text-white',
                                'reserved' => 'bg-info text-dark',
                                'maintenance' => 'bg-secondary text-white',
                                default => 'bg-light text-dark',
                            };
                        @endphp
                        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
                            <div class="card h-100 p-2 p-sm-3 {{ $cardBorder }} rounded-3 d-flex flex-column justify-content-between card-hover shadow-2xs">
                                <div>
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="fw-bold mb-0 text-dark font-mono">T{{ $table->table_number }}</h6>
                                        <span class="badge {{ $statusBadge }} small text-uppercase rounded-pill px-2 py-1 font-mono" style="font-size: 0.65rem;">
                                            {{ $table->status }}
                                        </span>
                                    </div>
                                    <div class="small text-muted mb-2" style="font-size: 0.75rem;">
                                        <i class="bi bi-people me-1"></i> Seats {{ $table->capacity }}
                                    </div>

                                    @if($table->status === 'occupied' && $table->activeOrder)
                                        <div class="p-2 border rounded-3 bg-white small mb-2 shadow-2xs">
                                            <div class="fw-bold text-primary font-mono" style="font-size: 0.75rem;">{{ $table->activeOrder->order_number }}</div>
                                            <div class="text-muted" style="font-size: 0.7rem;">Server: {{ $table->activeOrder->waiter?->name ?? 'Staff' }}</div>
                                            <a href="{{ route('orders.show', $table->activeOrder) }}" class="btn btn-xs btn-outline-primary mt-1 w-100 rounded-pill py-0" style="font-size: 0.7rem;">View Order</a>
                                        </div>
                                    @endif

                                    @if($table->merged_with_table_id)
                                        <span class="badge bg-warning bg-opacity-25 text-dark border small rounded-pill px-2" style="font-size: 0.65rem;">
                                            Merged w/ T{{ $table->mergedWith?->table_number }}
                                        </span>
                                    @endif
                                </div>

                                <div class="pt-2 border-top d-flex justify-content-between align-items-center mt-2">
                                    @if($table->status === 'occupied' && $table->activeOrder)
                                        <button type="button" class="btn btn-sm btn-light border rounded-circle p-1" style="width: 28px; height: 28px;" data-bs-toggle="modal" data-bs-target="#transferModal{{ $table->id }}" title="Transfer Table">
                                            <i class="bi bi-arrow-left-right text-primary"></i>
                                        </button>
                                    @endif
                                    <button type="button" class="btn btn-sm btn-light border rounded-circle p-1" style="width: 28px; height: 28px;" data-bs-toggle="modal" data-bs-target="#mergeModal{{ $table->id }}" title="Merge Table">
                                        <i class="bi bi-union text-secondary"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-light border rounded-circle p-1" style="width: 28px; height: 28px;" data-bs-toggle="modal" data-bs-target="#editTableModal{{ $table->id }}" title="Edit">
                                        <i class="bi bi-pencil text-muted"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Transfer Modal -->
                        @if($table->status === 'occupied' && $table->activeOrder)
                        <div class="modal fade" id="transferModal{{ $table->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border-0 shadow-lg rounded-4">
                                    <form method="POST" action="{{ route('tables.transfer', $table) }}">
                                        @csrf
                                        <div class="modal-header">
                                            <h6 class="modal-title fw-bold">Transfer Order from Table {{ $table->table_number }}</h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="small text-muted">Select an available target table to move the active order:</p>
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Target Table</label>
                                                <select name="target_table_id" class="form-select rounded-3" required>
                                                    <option value="">Select available table...</option>
                                                    @foreach($diningAreas as $dA)
                                                        <optgroup label="{{ $dA->name }}">
                                                            @foreach($dA->tables->where('status', 'available') as $tAvail)
                                                                <option value="{{ $tAvail->id }}">Table {{ $tAvail->table_number }} (Seats {{ $tAvail->capacity }})</option>
                                                            @endforeach
                                                        </optgroup>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary rounded-pill px-3">Confirm Transfer</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Merge Modal -->
                        <div class="modal fade" id="mergeModal{{ $table->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border-0 shadow-lg rounded-4">
                                    <form method="POST" action="{{ route('tables.merge', $table) }}">
                                        @csrf
                                        <div class="modal-header">
                                            <h6 class="modal-title fw-bold">Merge Table {{ $table->table_number }} with another Table</h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="small text-muted">Merge Table {{ $table->table_number }} into a target table for large party seating:</p>
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Target Table</label>
                                                <select name="target_table_id" class="form-select rounded-3" required>
                                                    <option value="">Select target table...</option>
                                                    @foreach($diningAreas as $dA)
                                                        <optgroup label="{{ $dA->name }}">
                                                            @foreach($dA->tables->where('id', '!=', $table->id) as $tOther)
                                                                <option value="{{ $tOther->id }}">Table {{ $tOther->table_number }}</option>
                                                            @endforeach
                                                        </optgroup>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary rounded-pill px-3">Merge Tables</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Edit Table Modal -->
                        <div class="modal fade" id="editTableModal{{ $table->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border-0 shadow-lg rounded-4">
                                    <form method="POST" action="{{ route('tables.update', $table) }}">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header">
                                            <h6 class="modal-title fw-bold">Edit Table {{ $table->table_number }}</h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Dining Area</label>
                                                <select name="dining_area_id" class="form-select rounded-3" required>
                                                    @foreach($diningAreas as $dA)
                                                        <option value="{{ $dA->id }}" {{ $table->dining_area_id == $dA->id ? 'selected' : '' }}>
                                                            {{ $dA->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Table Identifier / Number</label>
                                                <input type="text" name="table_number" class="form-control rounded-3" value="{{ $table->table_number }}" required>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Seating Capacity</label>
                                                <input type="number" name="capacity" class="form-control rounded-3" value="{{ $table->capacity }}" required min="1">
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Table Status</label>
                                                <select name="status" class="form-select rounded-3" required>
                                                    <option value="available" {{ $table->status === 'available' ? 'selected' : '' }}>Available</option>
                                                    <option value="occupied" {{ $table->status === 'occupied' ? 'selected' : '' }}>Occupied</option>
                                                    <option value="reserved" {{ $table->status === 'reserved' ? 'selected' : '' }}>Reserved</option>
                                                    <option value="maintenance" {{ $table->status === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary rounded-pill px-3">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-muted py-3 text-center">No tables configured in this dining area.</div>
                    @endforelse
                </div>
            </div>
        </div>
    @endforeach
</div>

<!-- Add Table Modal -->
<div class="modal fade" id="addTableModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form method="POST" action="{{ route('tables.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Register New Table</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Dining Area <span class="text-danger">*</span></label>
                        <select name="dining_area_id" class="form-select rounded-3" required>
                            @foreach($diningAreas as $dA)
                                <option value="{{ $dA->id }}">{{ $dA->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Table Identifier / Number <span class="text-danger">*</span></label>
                        <input type="text" name="table_number" class="form-control rounded-3" placeholder="e.g. T7, VIP-3" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Seating Capacity <span class="text-danger">*</span></label>
                        <input type="number" name="capacity" class="form-control rounded-3" value="4" required min="1" max="50">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Initial Status</label>
                        <select name="status" class="form-select rounded-3" required>
                            <option value="available">Available</option>
                            <option value="reserved">Reserved</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-kerefa rounded-pill px-4">Register Table</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
