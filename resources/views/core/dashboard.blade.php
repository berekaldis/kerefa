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
            <!-- Test Thermal Print on IRP 200D Button -->
            <button type="button" class="btn btn-sm btn-outline-dark rounded-pill shadow-sm px-3 font-mono" onclick="testIrp200dPrint()">
                <i class="bi bi-printer-fill text-warning me-1"></i> Test IRP 200D
            </button>
        </div>
    </div>

    <!-- KPI Metric Cards (Real DB counts) -->
    <div class="row g-2 g-sm-3 mb-3">
        <div class="col-6 col-xl-3">
            <div class="card h-100 p-3 border-0 shadow-sm rounded-4 card-hover">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">Active Branches</div>
                        <h3 class="fw-bold text-dark font-mono mb-0 mt-1">{{ $stats['branches_count'] }}</h3>
                    </div>
                    <div class="rounded-3 bg-primary bg-opacity-10 p-2 p-sm-3 text-primary fs-4">
                        <i class="bi bi-buildings"></i>
                    </div>
                </div>
                <div class="mt-2 text-muted small" style="font-size: 0.75rem;">
                    Multi-branch enabled
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="card h-100 p-3 border-0 shadow-sm rounded-4 card-hover">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">Staff Accounts</div>
                        <h3 class="fw-bold text-dark font-mono mb-0 mt-1">{{ $stats['users_active'] }} <small class="fs-6 text-muted fw-normal">/ {{ $stats['users_total'] }}</small></h3>
                    </div>
                    <div class="rounded-3 bg-success bg-opacity-10 p-2 p-sm-3 text-success fs-4">
                        <i class="bi bi-people"></i>
                    </div>
                </div>
                <div class="mt-2 text-muted small" style="font-size: 0.75rem;">
                    Active across roles
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="card h-100 p-3 border-0 shadow-sm rounded-4 card-hover">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">Branch Staff</div>
                        <h3 class="fw-bold text-dark font-mono mb-0 mt-1">{{ $stats['branch_users_count'] }}</h3>
                    </div>
                    <div class="rounded-3 bg-warning bg-opacity-10 p-2 p-sm-3 text-warning fs-4">
                        <i class="bi bi-person-workspace"></i>
                    </div>
                </div>
                <div class="mt-2 text-muted small" style="font-size: 0.75rem;">
                    {{ $currentBranch?->code ?? 'HQ' }} Assigned
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="card h-100 p-3 border-0 shadow-sm rounded-4 card-hover">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">Security Roles</div>
                        <h3 class="fw-bold text-dark font-mono mb-0 mt-1">{{ $stats['roles_count'] }}</h3>
                    </div>
                    <div class="rounded-3 bg-info bg-opacity-10 p-2 p-sm-3 text-info fs-4">
                        <i class="bi bi-shield-check"></i>
                    </div>
                </div>
                <div class="mt-2 text-muted small" style="font-size: 0.75rem;">
                    12 Granular RBAC
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Operational Navigation Modules -->
    <div class="card mb-3 border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center border-bottom">
            <span class="fw-bold text-dark"><i class="bi bi-grid-fill me-2 text-warning"></i>Operational & Management Modules</span>
            <span class="badge bg-light text-secondary border font-mono rounded-pill px-2 py-1">Quick Access</span>
        </div>
        <div class="card-body p-3">
            <div class="row g-2 g-sm-3">
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="p-3 border rounded-4 bg-white card-hover h-100 d-flex flex-column justify-content-between shadow-2xs">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-warning bg-opacity-10 text-warning p-2 rounded-circle"><i class="bi bi-grid-3x3-gap-fill fs-5"></i></span>
                                <h6 class="fw-bold mb-0 text-dark">Dedicated POS</h6>
                            </div>
                            <p class="text-muted small mb-3">Touchscreen order terminal, table seating, and instant thermal printing to IRP 200D.</p>
                        </div>
                        <a href="{{ route('pos.terminal') }}" target="_blank" class="btn btn-sm btn-kerefa rounded-pill w-100">Launch POS Terminal <i class="bi bi-box-arrow-up-right ms-1 small"></i></a>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="p-3 border rounded-4 bg-white card-hover h-100 d-flex flex-column justify-content-between shadow-2xs">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-success bg-opacity-10 text-success p-2 rounded-circle"><i class="bi bi-receipt fs-5"></i></span>
                                <h6 class="fw-bold mb-0 text-dark">Orders Tracking</h6>
                            </div>
                            <p class="text-muted small mb-3">POS order tracking, dine-in, takeaway & delivery history, and thermal ticket reprinting.</p>
                        </div>
                        <a href="{{ route('orders.index') }}" class="btn btn-sm btn-outline-success rounded-pill w-100">View Orders</a>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="p-3 border rounded-4 bg-white card-hover h-100 d-flex flex-column justify-content-between shadow-2xs">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-primary bg-opacity-10 text-primary p-2 rounded-circle"><i class="bi bi-grid-3x3 fs-5"></i></span>
                                <h6 class="fw-bold mb-0 text-dark">Table Floor Plan</h6>
                            </div>
                            <p class="text-muted small mb-3">Floor seating occupancy, table status, guest table transfer, and table merging.</p>
                        </div>
                        <a href="{{ route('tables.index') }}" class="btn btn-sm btn-outline-primary rounded-pill w-100">View Tables</a>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="p-3 border rounded-4 bg-white card-hover h-100 d-flex flex-column justify-content-between shadow-2xs border-warning">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-warning bg-opacity-10 text-warning p-2 rounded-circle"><i class="bi bi-printer-fill fs-5"></i></span>
                                <h6 class="fw-bold mb-0 text-dark">Station Mapping</h6>
                            </div>
                            <p class="text-muted small mb-3">Configure kitchen and bar order stations and assign thermal printers (Model: IRP 200D).</p>
                        </div>
                        <a href="{{ route('admin.stations.index') }}" class="btn btn-sm btn-outline-dark rounded-pill w-100">Configure Stations</a>
                    </div>
                </div>

                <!-- Phase 4-7 Module Placeholders -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="p-3 border rounded-4 bg-light bg-opacity-50 h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="bi bi-boxes fs-4 text-success"></i>
                                <h6 class="fw-bold mb-0 text-dark">Stock & Inventory</h6>
                            </div>
                            <p class="text-muted small mb-3">Multi-location stores, purchase orders, goods receiving, unit conversions, stock ledger.</p>
                        </div>
                        <span class="badge bg-secondary font-mono rounded-pill align-self-start">Phase 4 Next</span>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="p-3 border rounded-4 bg-light bg-opacity-50 h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="bi bi-book fs-4 text-primary"></i>
                                <h6 class="fw-bold mb-0 text-dark">F&B Recipes</h6>
                            </div>
                            <p class="text-muted small mb-3">Portion controls, recipe costing, yield %, batch preparation, automated consumption.</p>
                        </div>
                        <span class="badge bg-secondary font-mono rounded-pill align-self-start">Phase 5</span>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="p-3 border rounded-4 bg-light bg-opacity-50 h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="bi bi-journal-text fs-4 text-dark"></i>
                                <h6 class="fw-bold mb-0 text-dark">Finance & Accounting</h6>
                            </div>
                            <p class="text-muted small mb-3">General ledger, balanced journals, trial balance, P&L, balance sheet, multi-branch.</p>
                        </div>
                        <span class="badge bg-secondary font-mono rounded-pill align-self-start">Phase 6</span>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="p-3 border rounded-4 bg-light bg-opacity-50 h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="bi bi-people fs-4 text-secondary"></i>
                                <h6 class="fw-bold mb-0 text-dark">HR Management</h6>
                            </div>
                            <p class="text-muted small mb-3">Staff directory, manual attendance, shifts, leave requests, monthly payroll runs.</p>
                        </div>
                        <span class="badge bg-secondary font-mono rounded-pill align-self-start">Phase 7</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Audit Activity Log Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center border-bottom">
            <span class="fw-bold text-dark"><i class="bi bi-clock-history me-2 text-secondary"></i>Recent System Audit Trail</span>
            @if(auth()->user()->hasPermission('audit.view'))
                <a href="{{ route('audit-logs.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3 font-mono" style="font-size: 0.72rem;">View Full Log</a>
            @endif
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
                                    <span class="badge bg-secondary bg-opacity-25 text-dark rounded-pill px-2 py-1 font-mono" style="font-size: 0.68rem;">{{ ucfirst($log->module) }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-2 py-1 font-mono" style="font-size: 0.68rem;">{{ strtoupper($log->action) }}</span>
                                </td>
                                <td class="small text-dark">{{ $log->description ?? '-' }}</td>
                                <td class="pe-3 small text-muted font-mono">{{ $log->ip_address ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No audit activity logged yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Test Print on Thermal Printer Model: IRP 200D
    function testIrp200dPrint() {
        const testTicketHtml = `
            <div class="irp200d-ticket-header">
                <div class="irp200d-title">KEREFA RESTAURANT</div>
                <div style="font-size: 10px; margin-top: 2px;">PRINTER SELF-TEST: MODEL IRP 200D</div>
                <div class="irp200d-banner">*** PRINTER READY & CONNECTED ***</div>
            </div>
            <div class="irp200d-meta-row">
                <span><strong>STATUS :</strong> 203 DPI / 80mm</span>
                <span><strong>SPEED :</strong> 200 mm/s</span>
            </div>
            <div class="irp200d-meta-row">
                <span><strong>BRANCH :</strong> {{ $currentBranch?->name ?? 'Main Restaurant' }}</span>
            </div>
            <div class="irp200d-meta-row">
                <span><strong>TESTER :</strong> {{ auth()->user()->name }}</span>
                <span>${new Date().toLocaleTimeString()}</span>
            </div>
            <table class="irp200d-items-table">
                <thead>
                    <tr>
                        <th style="width: 32px;">TEST</th>
                        <th>DEPARTMENT ROUTING CHANNEL</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="irp200d-item-qty">[ OK ]</td>
                        <td class="irp200d-item-name">Kitchen Order Ticket (KOT) Routing</td>
                    </tr>
                    <tr>
                        <td class="irp200d-item-qty">[ OK ]</td>
                        <td class="irp200d-item-name">Bar Order Ticket (BOT) Routing</td>
                    </tr>
                    <tr>
                        <td class="irp200d-item-qty">[ OK ]</td>
                        <td class="irp200d-item-name">ESC/POS Monospace Formatting</td>
                    </tr>
                </tbody>
            </table>
            <div class="irp200d-meta-row" style="font-weight: bold;">
                <span>INITIAL SCOPE:</span>
                <span>NO BILL PRINT (ORDER TICKET ONLY)</span>
            </div>
            <div class="irp200d-cutter-feed">
                - - - - - - - - - - - - - [ IRP 200D CUT LINE ] - - - - - - - - - - - - -
            </div>
        `;
        window.printIrp200dTicket(testTicketHtml);
    }
</script>
@endpush
@endsection
