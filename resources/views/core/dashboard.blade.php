@extends('layouts.app')

@section('title', 'Executive Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Welcome Header Banner -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold mb-1">Welcome back, {{ $user->name }}!</h4>
            <p class="text-muted small mb-0">
                Operating Branch: <span class="fw-semibold text-dark">{{ $currentBranch?->name ?? 'Headquarters' }}</span> | Role: <span class="badge bg-secondary">{{ $user->roles->first()?->name ?? 'Staff' }}</span>
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2">
                <i class="bi bi-circle-fill me-1 small"></i> System Live & Operational
            </span>
        </div>
    </div>

    <!-- KPI Metric Cards (Real DB counts) -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 border-start border-4 border-primary">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small text-uppercase fw-bold">Active Branches</div>
                            <h3 class="fw-bold text-dark mb-0 mt-1">{{ $stats['branches_count'] }}</h3>
                        </div>
                        <div class="rounded-3 bg-primary bg-opacity-10 p-3 text-primary fs-4">
                            <i class="bi bi-buildings"></i>
                        </div>
                    </div>
                    <div class="mt-2 text-muted small">
                        Multi-branch architecture ready
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 border-start border-4 border-success">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small text-uppercase fw-bold">System Users</div>
                            <h3 class="fw-bold text-dark mb-0 mt-1">{{ $stats['users_active'] }} <span class="fs-6 text-muted fw-normal">/ {{ $stats['users_total'] }}</span></h3>
                        </div>
                        <div class="rounded-3 bg-success bg-opacity-10 p-3 text-success fs-4">
                            <i class="bi bi-people"></i>
                        </div>
                    </div>
                    <div class="mt-2 text-muted small">
                        Active staff across departments
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 border-start border-4 border-warning">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small text-uppercase fw-bold">Branch Staff</div>
                            <h3 class="fw-bold text-dark mb-0 mt-1">{{ $stats['branch_users_count'] }}</h3>
                        </div>
                        <div class="rounded-3 bg-warning bg-opacity-10 p-3 text-warning fs-4">
                            <i class="bi bi-person-workspace"></i>
                        </div>
                    </div>
                    <div class="mt-2 text-muted small">
                        Assigned to {{ $currentBranch?->code ?? 'HQ' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 border-start border-4 border-info">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small text-uppercase fw-bold">System Roles</div>
                            <h3 class="fw-bold text-dark mb-0 mt-1">{{ $stats['roles_count'] }}</h3>
                        </div>
                        <div class="rounded-3 bg-info bg-opacity-10 p-3 text-info fs-4">
                            <i class="bi bi-shield-check"></i>
                        </div>
                    </div>
                    <div class="mt-2 text-muted small">
                        Role-based permissions active
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Operational Navigation Modules -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-bold"><i class="bi bi-grid-fill me-2 text-primary"></i>Operational & Management Modules</span>
                    <span class="badge bg-light text-muted border">Kerefa ERP Modules</span>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4 col-lg-3">
                            <div class="p-3 border rounded-3 bg-light h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <i class="bi bi-grid-3x3-gap fs-4 text-warning"></i>
                                        <h6 class="fw-bold mb-0">POS Terminal</h6>
                                    </div>
                                    <p class="text-muted small mb-3">Order taking, table layout, kitchen/bar dispatch (No customer billing initial release).</p>
                                </div>
                                <span class="badge bg-warning bg-opacity-25 text-dark border">Phase 3 Ready</span>
                            </div>
                        </div>

                        <div class="col-md-4 col-lg-3">
                            <div class="p-3 border rounded-3 bg-light h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <i class="bi bi-tv fs-4 text-danger"></i>
                                        <h6 class="fw-bold mb-0">Kitchen KDS</h6>
                                    </div>
                                    <p class="text-muted small mb-3">Dedicated Kitchen Display System for automated food preparation ticket routing.</p>
                                </div>
                                <span class="badge bg-secondary bg-opacity-25 text-dark border">Phase 3</span>
                            </div>
                        </div>

                        <div class="col-md-4 col-lg-3">
                            <div class="p-3 border rounded-3 bg-light h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <i class="bi bi-cup-straw fs-4 text-info"></i>
                                        <h6 class="fw-bold mb-0">Bar BDS</h6>
                                    </div>
                                    <p class="text-muted small mb-3">Dedicated Bar Display System for automated beverage preparation ticket routing.</p>
                                </div>
                                <span class="badge bg-secondary bg-opacity-25 text-dark border">Phase 3</span>
                            </div>
                        </div>

                        <div class="col-md-4 col-lg-3">
                            <div class="p-3 border rounded-3 bg-light h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <i class="bi bi-boxes fs-4 text-success"></i>
                                        <h6 class="fw-bold mb-0">Inventory & Stock</h6>
                                    </div>
                                    <p class="text-muted small mb-3">Stock ledger, transfers, purchase orders, goods receiving, and physical counts.</p>
                                </div>
                                <span class="badge bg-secondary bg-opacity-25 text-dark border">Phase 4</span>
                            </div>
                        </div>

                        <div class="col-md-4 col-lg-3">
                            <div class="p-3 border rounded-3 bg-light h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <i class="bi bi-book fs-4 text-primary"></i>
                                        <h6 class="fw-bold mb-0">F&B Recipes</h6>
                                    </div>
                                    <p class="text-muted small mb-3">Menu engineering, ingredient portions, recipe costing, yield, and food cost %.</p>
                                </div>
                                <span class="badge bg-secondary bg-opacity-25 text-dark border">Phase 5</span>
                            </div>
                        </div>

                        <div class="col-md-4 col-lg-3">
                            <div class="p-3 border rounded-3 bg-light h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <i class="bi bi-journal-text fs-4 text-dark"></i>
                                        <h6 class="fw-bold mb-0">Finance & Ledger</h6>
                                    </div>
                                    <p class="text-muted small mb-3">Double-entry accounting, balanced journals, trial balance, P&L, and balance sheet.</p>
                                </div>
                                <span class="badge bg-secondary bg-opacity-25 text-dark border">Phase 6</span>
                            </div>
                        </div>

                        <div class="col-md-4 col-lg-3">
                            <div class="p-3 border rounded-3 bg-light h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <i class="bi bi-people fs-4 text-secondary"></i>
                                        <h6 class="fw-bold mb-0">HR & Payroll</h6>
                                    </div>
                                    <p class="text-muted small mb-3">Staff profiles, manual shift attendance, leave requests, and payroll records.</p>
                                </div>
                                <span class="badge bg-secondary bg-opacity-25 text-dark border">Phase 7</span>
                            </div>
                        </div>

                        <div class="col-md-4 col-lg-3">
                            <div class="p-3 border rounded-3 bg-white border-primary border-opacity-50 h-100 d-flex flex-column justify-content-between shadow-sm">
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <i class="bi bi-person-badge fs-4 text-primary"></i>
                                        <h6 class="fw-bold mb-0">Users & Access</h6>
                                    </div>
                                    <p class="text-muted small mb-3">Manage system users, 12 RBAC roles, branch assignments, and security.</p>
                                </div>
                                <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-primary">Manage Users</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Audit Activity Log Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-bold"><i class="bi bi-clock-history me-2 text-secondary"></i>Recent System Audit Trail</span>
                    @if(auth()->user()->hasPermission('audit.view'))
                        <a href="{{ route('audit-logs.index') }}" class="btn btn-sm btn-outline-secondary">View All Logs</a>
                    @endif
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3" style="width: 180px;">Timestamp</th>
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
                                        <td class="ps-3 small text-muted">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                        <td>
                                            <span class="fw-semibold">{{ $log->user?->name ?? 'System' }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">{{ $log->branch?->code ?? 'Global' }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary bg-opacity-25 text-dark">{{ ucfirst($log->module) }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">{{ strtoupper($log->action) }}</span>
                                        </td>
                                        <td class="small">{{ $log->description ?? '-' }}</td>
                                        <td class="pe-3 small text-muted font-monospace">{{ $log->ip_address ?? '-' }}</td>
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
    </div>
</div>
@endsection
