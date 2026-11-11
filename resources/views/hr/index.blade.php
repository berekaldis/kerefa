@extends('layouts.app')

@section('title', 'Human Resources & Staff Operations')

@section('content')
<div class="container-fluid px-1 px-sm-2 px-md-3">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
        <div>
            <h4 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                <i class="bi bi-people text-warning"></i>
                <span>Human Resources & Staff Management</span>
            </h4>
            <span class="text-muted small">Staff roster, waiter performance, shift attendance, and departments</span>
        </div>
        @if(auth()->user()->hasPermission('hr.attendance'))
        <div>
            <button type="button" class="btn btn-sm btn-kerefa rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#newAttendanceModal">
                <i class="bi bi-check2-circle me-1"></i> Log Shift Attendance
            </button>
        </div>
        @endif
    </div>

    <!-- Stats -->
    <div class="row g-2 g-sm-3 mb-3">
        <div class="col-6 col-md-4">
            <div class="card p-3 border-0 shadow-sm rounded-4 h-100">
                <div class="text-muted small fw-bold text-uppercase">Total Active Staff</div>
                <h3 class="fw-bold text-dark font-mono mt-1 mb-0">{{ $stats['total_staff'] }}</h3>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card p-3 border-0 shadow-sm rounded-4 h-100">
                <div class="text-muted small fw-bold text-uppercase">Present Today</div>
                <h3 class="fw-bold text-success font-mono mt-1 mb-0">{{ $stats['present_today'] }}</h3>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card p-3 border-0 shadow-sm rounded-4 h-100">
                <div class="text-muted small fw-bold text-uppercase">Departments</div>
                <h3 class="fw-bold text-primary font-mono mt-1 mb-0">{{ $stats['departments_count'] }}</h3>
            </div>
        </div>
    </div>

    <!-- Waiter / Server Sales Performance Section -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-3">
        <div class="card-header bg-white py-3 px-3 border-bottom">
            <span class="fw-bold text-dark"><i class="bi bi-trophy-fill me-2 text-warning"></i>Top Server / Waiter Performance</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="small text-uppercase text-muted">
                            <th class="ps-3">Server / Waiter</th>
                            <th class="text-center">Orders Handled</th>
                            <th class="pe-3 text-end">Total Sales Revenue (ETB)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($waiterPerformance as $perf)
                            <tr>
                                <td class="ps-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                            <i class="bi bi-person-fill"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">{{ $perf->waiter?->name ?? 'Staff' }}</div>
                                            <small class="text-muted font-mono">{{ $perf->waiter?->username }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center font-mono fw-bold">{{ $perf->orders_count }}</td>
                                <td class="pe-3 text-end font-mono fw-bold text-success fs-6">
                                    ETB {{ number_format($perf->total_revenue, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted small">No performance data recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Employee Roster Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-3">
        <div class="card-header bg-white py-3 px-3 border-bottom">
            <span class="fw-bold text-dark"><i class="bi bi-person-lines-fill me-2 text-primary"></i>Active Employee Directory</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="small text-uppercase text-muted">
                            <th class="ps-3">Employee Name</th>
                            <th>Username</th>
                            <th>Department</th>
                            <th>Assigned Role</th>
                            <th class="pe-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employees as $emp)
                            <tr>
                                <td class="ps-3 fw-semibold text-dark">{{ $emp->name }}</td>
                                <td class="font-mono small text-muted">{{ $emp->username }}</td>
                                <td>
                                    <span class="badge bg-light text-secondary border rounded-pill">{{ $emp->department?->name ?? 'General' }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-dark rounded-pill">{{ $emp->roles->first()?->name ?? 'Staff' }}</span>
                                </td>
                                <td class="pe-3 text-center">
                                    <span class="badge bg-success rounded-pill">ACTIVE</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Attendance Modal -->
    <div class="modal fade" id="newAttendanceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('hr.attendance.record') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title fs-6 fw-bold">Log Shift Attendance</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Select Employee</label>
                            <select name="user_id" class="form-select form-select-sm" required>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->department?->name ?? 'Staff' }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Attendance Status</label>
                            <select name="status" class="form-select form-select-sm" required>
                                <option value="present">Present (On Shift)</option>
                                <option value="late">Late Arrival</option>
                                <option value="on_leave">Authorized Leave / Day Off</option>
                                <option value="absent">Absent</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Shift Notes</label>
                            <input type="text" name="notes" class="form-control form-control-sm" placeholder="Shift or station assignment...">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-primary fw-bold">Save Attendance</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
