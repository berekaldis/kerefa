@extends('layouts.app')

@section('title', 'System Audit Trail')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">System Audit Trail</h4>
            <p class="text-muted small mb-0">Immutable system activity logs for compliance, security, and operational accountability.</p>
        </div>
        <span class="badge bg-secondary p-2">
            <i class="bi bi-shield-check me-1"></i> Full Traceability Active
        </span>
    </div>

    <!-- Filter Card -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('audit-logs.index') }}" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small text-muted">Module</label>
                    <select name="module" class="form-select">
                        <option value="">All Modules</option>
                        @foreach($modules as $mod)
                            <option value="{{ $mod }}" {{ ($filters['module'] ?? '') == $mod ? 'selected' : '' }}>
                                {{ ucfirst($mod) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small text-muted">Action</label>
                    <select name="action" class="form-select">
                        <option value="">All Actions</option>
                        @foreach($actions as $act)
                            <option value="{{ $act }}" {{ ($filters['action'] ?? '') == $act ? 'selected' : '' }}>
                                {{ strtoupper($act) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small text-muted">User</label>
                    <select name="user_id" class="form-select">
                        <option value="">All Staff</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ ($filters['user_id'] ?? '') == $u->id ? 'selected' : '' }}>
                                {{ $u->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small text-muted">Branch</label>
                    <select name="branch_id" class="form-select">
                        <option value="">All Branches</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ ($filters['branch_id'] ?? '') == $b->id ? 'selected' : '' }}>
                                {{ $b->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small text-muted">Date From</label>
                    <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-filter me-1"></i> Filter
                    </button>
                    <a href="{{ route('audit-logs.index') }}" class="btn btn-outline-secondary" title="Reset">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-bold">Audit Records ({{ $logs->total() }})</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width: 170px;">Date & Time</th>
                            <th>User</th>
                            <th>Branch</th>
                            <th>Module</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th>IP Address</th>
                            <th class="pe-3 text-end">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td class="ps-3 small text-muted font-monospace">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $log->user?->name ?? 'System' }}</div>
                                    <small class="text-muted">{{ $log->user?->username ? '@'.$log->user->username : '' }}</small>
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
                                <td class="small text-muted font-monospace">{{ $log->ip_address ?? '-' }}</td>
                                <td class="pe-3 text-end">
                                    @if($log->old_values || $log->new_values)
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#logModal{{ $log->id }}">
                                            <i class="bi bi-code-slash me-1"></i> Diff
                                        </button>

                                        <!-- Log Diff Modal -->
                                        <div class="modal fade" id="logModal{{ $log->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-lg text-start">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h6 class="modal-title fw-bold">Audit Event #{{ $log->id }} - {{ $log->action }} on {{ $log->module }}</h6>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row g-3">
                                                            @if($log->old_values)
                                                                <div class="col-md-6">
                                                                    <div class="fw-bold small text-danger mb-2"><i class="bi bi-dash-circle me-1"></i>Previous Values</div>
                                                                    <pre class="bg-dark text-white p-3 rounded small" style="max-height: 250px; overflow-y: auto;"><code>{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                                                                </div>
                                                            @endif
                                                            @if($log->new_values)
                                                                <div class="col-md-{{ $log->old_values ? '6' : '12' }}">
                                                                    <div class="fw-bold small text-success mb-2"><i class="bi bi-plus-circle me-1"></i>New Values</div>
                                                                    <pre class="bg-dark text-white p-3 rounded small" style="max-height: 250px; overflow-y: auto;"><code>{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <div class="mt-3 pt-2 border-top small text-muted">
                                                            <div><strong>User Agent:</strong> {{ $log->user_agent ?? 'N/A' }}</div>
                                                            <div><strong>Target Record:</strong> {{ $log->record_type }} #{{ $log->record_id }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No audit logs found matching criteria.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($logs->hasPages())
            <div class="card-footer bg-white py-3">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
