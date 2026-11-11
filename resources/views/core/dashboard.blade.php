@extends('layouts.app')

@section('title', 'Executive Dashboard')

@section('content')
<div class="container-fluid px-1 px-sm-2 px-md-3">
    <!-- Welcome Header Banner -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-warning bg-opacity-10 text-warning rounded-circle p-2"><i class="bi bi-speedometer2 fs-5"></i></span>
                <div>
                    <h4 class="fw-bold mb-0 text-dark">Welcome back, {{ $user->name }}!</h4>
                    <span class="text-muted small">
                        Branch: <strong class="text-dark">{{ $currentBranch?->name ?? 'Headquarters' }}</strong> &bull; Role: <span class="badge bg-dark rounded-pill px-2 font-mono" style="font-size: 0.68rem;">{{ $user->roles->first()?->name ?? 'Staff' }}</span>
                    </span>
                </div>
            </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-2 small">
                <span class="pulse-dot me-1"></span> Live ERP Operations
            </span>
            @if(auth()->user()->hasPermission('pos.view'))
                <!-- Quick Launch POS Button -->
                <a href="{{ route('pos.terminal') }}" target="_blank" class="btn btn-sm btn-kerefa rounded-pill shadow-sm px-3 fw-bold">
                    <i class="bi bi-grid-fill me-1"></i> Launch POS Terminal
                </a>
            @endif
        </div>
    </div>

    <!-- Live Restaurant Operational KPI Cards -->
    <div class="row g-2 g-sm-3 mb-3">
        <!-- Today's Orders -->
        <div class="col-6 col-xl-3">
            <div class="card h-100 p-3 border-0 shadow-sm rounded-4 card-hover">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">Today's Orders</div>
                        <h3 class="fw-bold text-dark font-mono mb-0 mt-1">{{ $stats['orders_today'] }}</h3>
                    </div>
                    <div class="rounded-3 bg-primary bg-opacity-10 p-2 p-sm-3 text-primary fs-4">
                        <i class="bi bi-receipt-cutoff"></i>
                    </div>
                </div>
                <div class="mt-2 text-muted small" style="font-size: 0.75rem;">
                    @if(auth()->user()->hasPermission('pos.view'))
                        <a href="{{ route('orders.index') }}" class="text-decoration-none text-primary fw-semibold">View tracking &rarr;</a>
                    @else
                        <span class="text-muted">Today's transactions</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Dining Tables Occupancy -->
        <div class="col-6 col-xl-3">
            <div class="card h-100 p-3 border-0 shadow-sm rounded-4 card-hover">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">Table Occupancy</div>
                        <h3 class="fw-bold text-dark font-mono mb-0 mt-1">{{ $stats['tables_occupied'] }} <small class="fs-6 text-muted fw-normal">/ {{ $stats['tables_total'] }}</small></h3>
                    </div>
                    <div class="rounded-3 bg-warning bg-opacity-10 p-2 p-sm-3 text-warning fs-4">
                        <i class="bi bi-grid-3x3"></i>
                    </div>
                </div>
                <div class="mt-2 text-muted small" style="font-size: 0.75rem;">
                    <span class="text-success fw-bold">{{ $stats['tables_available'] }} Available</span>
                    @if(auth()->user()->hasPermission('pos.tables.manage') || auth()->user()->hasPermission('pos.view'))
                        &bull; <a href="{{ route('tables.index') }}" class="text-decoration-none text-muted">Floor &rarr;</a>
                    @endif
                </div>
            </div>
        </div>

        <!-- Order Stations (IRP 200D Printers) -->
        <div class="col-6 col-xl-3">
            <div class="card h-100 p-3 border-0 shadow-sm rounded-4 card-hover">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">Order Stations</div>
                        <h3 class="fw-bold text-dark font-mono mb-0 mt-1">{{ $stats['stations_count'] }}</h3>
                    </div>
                    <div class="rounded-3 bg-success bg-opacity-10 p-2 p-sm-3 text-success fs-4">
                        <i class="bi bi-printer-fill"></i>
                    </div>
                </div>
                <div class="mt-2 text-muted small" style="font-size: 0.75rem;">
                    @if(auth()->user()->hasPermission('settings.view'))
                        <a href="{{ route('admin.stations.index') }}" class="text-decoration-none text-success fw-semibold">Model: IRP 200D (80mm) &rarr;</a>
                    @else
                        Model: <strong>IRP 200D (80mm)</strong>
                    @endif
                </div>
            </div>
        </div>

        <!-- Active Menu Catalog -->
        <div class="col-6 col-xl-3">
            <div class="card h-100 p-3 border-0 shadow-sm rounded-4 card-hover">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">Menu Catalog</div>
                        <h3 class="fw-bold text-dark font-mono mb-0 mt-1">{{ $stats['menu_items_count'] }}</h3>
                    </div>
                    <div class="rounded-3 bg-info bg-opacity-10 p-2 p-sm-3 text-info fs-4">
                        <i class="bi bi-cup-hot-fill"></i>
                    </div>
                </div>
                <div class="mt-2 text-muted small" style="font-size: 0.75rem;">
                    @if(auth()->user()->hasPermission('pos.menu.manage'))
                        <a href="{{ route('menu.index') }}" class="text-decoration-none text-info fw-semibold">Manage catalog &rarr;</a>
                    @else
                        <span class="text-muted">Active menu items</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @php
        $userObj = auth()->user();
        $canSeePos = $userObj->hasPermission('pos.view') || $userObj->hasPermission('pos.orders.create') || $userObj->isSuperAdmin();
        $canSeeOrders = $userObj->hasPermission('pos.view') || $userObj->hasPermission('pos.orders.create') || $userObj->isSuperAdmin();
        $canSeeTables = $userObj->hasPermission('pos.tables.manage') || $userObj->hasPermission('pos.view') || $userObj->isSuperAdmin();
        $canSeeStations = $userObj->hasPermission('settings.manage') || $userObj->hasPermission('settings.view') || $userObj->isSuperAdmin();
        $canSeeInventory = $userObj->hasPermission('inventory.view') || $userObj->isSuperAdmin();
        $canSeeProcurement = $userObj->hasPermission('procurement.view') || $userObj->isSuperAdmin();
        $canSeeRecipes = $userObj->hasPermission('fb.recipes.view') || $userObj->isSuperAdmin();
        $canSeeFinance = $userObj->hasPermission('finance.view') || $userObj->isSuperAdmin();
        $canSeeHr = $userObj->hasPermission('hr.employees.view') || $userObj->isSuperAdmin();
        $hasAnyModule = $canSeePos || $canSeeOrders || $canSeeTables || $canSeeStations || $canSeeInventory || $canSeeProcurement || $canSeeRecipes || $canSeeFinance || $canSeeHr;
    @endphp

    @if($hasAnyModule)
    <!-- Active Operational Modules (Access-Controlled) -->
    <div class="card mb-3 border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center border-bottom">
            <span class="fw-bold text-dark"><i class="bi bi-grid-fill me-2 text-warning"></i>Active Restaurant Modules</span>
            <span class="badge bg-light text-secondary border font-mono rounded-pill px-2 py-1">Quick Access</span>
        </div>
        <div class="card-body p-3">
            <div class="row g-2 g-sm-3">
                @if($canSeePos)
                <!-- Dedicated POS Terminal -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="p-3 border rounded-4 bg-white card-hover h-100 d-flex flex-column justify-content-between shadow-2xs">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-warning bg-opacity-10 text-warning p-2 rounded-circle"><i class="bi bi-grid-3x3-gap-fill fs-5"></i></span>
                                <h6 class="fw-bold mb-0 text-dark">Dedicated POS</h6>
                            </div>
                            <p class="text-muted small mb-3">Touchscreen order terminal, visual floor seating plan, and instant thermal printing to IRP 200D.</p>
                        </div>
                        <a href="{{ route('pos.terminal') }}" target="_blank" class="btn btn-sm btn-kerefa rounded-pill w-100">Launch POS Terminal <i class="bi bi-box-arrow-up-right ms-1 small"></i></a>
                    </div>
                </div>
                @endif

                @if($canSeeOrders)
                <!-- Orders Tracking -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="p-3 border rounded-4 bg-white card-hover h-100 d-flex flex-column justify-content-between shadow-2xs">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-success bg-opacity-10 text-success p-2 rounded-circle"><i class="bi bi-receipt fs-5"></i></span>
                                <h6 class="fw-bold mb-0 text-dark">Orders Tracking</h6>
                            </div>
                            <p class="text-muted small mb-3">Real-time order history, kitchen/bar ticket routing status, and IRP 200D reprinting.</p>
                        </div>
                        <a href="{{ route('orders.index') }}" class="btn btn-sm btn-outline-success rounded-pill w-100">View Orders</a>
                    </div>
                </div>
                @endif

                @if($canSeeTables)
                <!-- Table Floor Plan -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="p-3 border rounded-4 bg-white card-hover h-100 d-flex flex-column justify-content-between shadow-2xs">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-primary bg-opacity-10 text-primary p-2 rounded-circle"><i class="bi bi-grid-3x3 fs-5"></i></span>
                                <h6 class="fw-bold mb-0 text-dark">Table Floor Plan</h6>
                            </div>
                            <p class="text-muted small mb-3">Dining areas, visual floor seating, table transfers, and table merging.</p>
                        </div>
                        <a href="{{ route('tables.index') }}" class="btn btn-sm btn-outline-primary rounded-pill w-100">View Tables</a>
                    </div>
                </div>
                @endif

                @if($canSeeInventory)
                <!-- Stock & Inventory -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="p-3 border rounded-4 bg-white card-hover h-100 d-flex flex-column justify-content-between shadow-2xs">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-info bg-opacity-10 text-info p-2 rounded-circle"><i class="bi bi-box-seam fs-5"></i></span>
                                <h6 class="fw-bold mb-0 text-dark">Stock & Inventory</h6>
                            </div>
                            <p class="text-muted small mb-3">Item balances, category thresholds, stock in/out adjustments, and valuation in ETB.</p>
                        </div>
                        <a href="{{ route('inventory.index') }}" class="btn btn-sm btn-outline-info rounded-pill w-100">Manage Inventory</a>
                    </div>
                </div>
                @endif

                @if($canSeeProcurement)
                <!-- Procurement & Suppliers -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="p-3 border rounded-4 bg-white card-hover h-100 d-flex flex-column justify-content-between shadow-2xs">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-secondary bg-opacity-10 text-secondary p-2 rounded-circle"><i class="bi bi-truck fs-5"></i></span>
                                <h6 class="fw-bold mb-0 text-dark">Procurement</h6>
                            </div>
                            <p class="text-muted small mb-3">Local vendor directory, purchase orders, and goods receiving notes (GRN).</p>
                        </div>
                        <a href="{{ route('procurement.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill w-100">Procurement & POs</a>
                    </div>
                </div>
                @endif

                @if($canSeeRecipes)
                <!-- F&B Recipes & Costing -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="p-3 border rounded-4 bg-white card-hover h-100 d-flex flex-column justify-content-between shadow-2xs">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-success bg-opacity-10 text-success p-2 rounded-circle"><i class="bi bi-book fs-5"></i></span>
                                <h6 class="fw-bold mb-0 text-dark">Recipes & Costing</h6>
                            </div>
                            <p class="text-muted small mb-3">Recipe ingredients, food cost percentage analysis, and portion gross margins.</p>
                        </div>
                        <a href="{{ route('fb.recipes.index') }}" class="btn btn-sm btn-outline-success rounded-pill w-100">View Recipes</a>
                    </div>
                </div>
                @endif

                @if($canSeeFinance)
                <!-- Finance & Operating Accounts -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="p-3 border rounded-4 bg-white card-hover h-100 d-flex flex-column justify-content-between shadow-2xs">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-danger bg-opacity-10 text-danger p-2 rounded-circle"><i class="bi bi-journal-text fs-5"></i></span>
                                <h6 class="fw-bold mb-0 text-dark">Operating Accounts</h6>
                            </div>
                            <p class="text-muted small mb-3">Sales revenue by payment method (Birr), daily operating expenses, and net profit.</p>
                        </div>
                        <a href="{{ route('finance.index') }}" class="btn btn-sm btn-outline-danger rounded-pill w-100">Finance & Expenses</a>
                    </div>
                </div>
                @endif

                @if($canSeeHr)
                <!-- Human Resources -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="p-3 border rounded-4 bg-white card-hover h-100 d-flex flex-column justify-content-between shadow-2xs">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-primary bg-opacity-10 text-primary p-2 rounded-circle"><i class="bi bi-people fs-5"></i></span>
                                <h6 class="fw-bold mb-0 text-dark">Human Resources</h6>
                            </div>
                            <p class="text-muted small mb-3">Employee directory, waiter sales performance, and daily shift attendance tracking.</p>
                        </div>
                        <a href="{{ route('hr.index') }}" class="btn btn-sm btn-outline-primary rounded-pill w-100">Staff Management</a>
                    </div>
                </div>
                @endif

                @if($canSeeStations)
                <!-- Station Mapping -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="p-3 border rounded-4 bg-white card-hover h-100 d-flex flex-column justify-content-between shadow-2xs border-warning">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-warning bg-opacity-10 text-warning p-2 rounded-circle"><i class="bi bi-printer-fill fs-5"></i></span>
                                <h6 class="fw-bold mb-0 text-dark">Station Mapping</h6>
                            </div>
                            <p class="text-muted small mb-3">Configure kitchen & bar order stations and assign thermal printers (Model: IRP 200D).</p>
                        </div>
                        <a href="{{ route('admin.stations.index') }}" class="btn btn-sm btn-outline-dark rounded-pill w-100">Configure Stations</a>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    @if(auth()->user()->hasPermission('audit.view') || auth()->user()->isSuperAdmin())
    <!-- Recent Audit Activity Log Table (Admin / Auditor Access Only) -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-3">
        <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center border-bottom">
            <span class="fw-bold text-dark"><i class="bi bi-clock-history me-2 text-secondary"></i>Recent System Audit Trail</span>
            <a href="{{ route('audit-logs.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3 font-mono" style="font-size: 0.72rem;">View Full Log</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="small text-uppercase text-muted">
                            <th class="ps-3" style="width: 170px;">Timestamp</th>
                            <th>User</th>
                            <th>Branch</th>
                            <th>Module</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th class="pe-3">IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentActivities as $log)
                            <tr>
                                <td class="ps-3 small font-mono text-muted">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                <td>
                                    <span class="fw-semibold text-dark">{{ $log->user?->name ?? 'System' }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border font-mono rounded-pill px-2 py-1">{{ $log->branch?->code ?? 'Global' }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary font-mono rounded-pill px-2 py-1">{{ strtoupper($log->module) }}</span>
                                </td>
                                <td>
                                    <span class="font-mono small fw-bold text-dark">{{ $log->action }}</span>
                                </td>
                                <td class="small text-muted">{{ $log->description ?? '-' }}</td>
                                <td class="pe-3 small font-mono text-muted">{{ $log->ip_address ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted small">No audit log entries found for this scope.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
