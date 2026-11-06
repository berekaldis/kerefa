@extends('layouts.app')

@section('title', 'User Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">User & Access Management</h4>
            <p class="text-muted small mb-0">Manage restaurant personnel, role assignments, and branch access controls.</p>
        </div>
        @if(auth()->user()->hasPermission('users.create'))
            <a href="{{ route('users.create') }}" class="btn btn-kerefa">
                <i class="bi bi-person-plus-fill me-1"></i> Add New User
            </a>
        @endif
    </div>

    <!-- Filter Card -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('users.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small text-muted">Search User</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search by name, username, email..." value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label small text-muted">Filter by Branch</label>
                    <select name="branch_id" class="form-select">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ ($filters['branch_id'] ?? '') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }} ({{ $branch->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small text-muted">Filter by Role</label>
                    <select name="role" class="form-select">
                        <option value="">All Roles</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->slug }}" {{ ($filters['role'] ?? '') == $role->slug ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-filter me-1"></i> Filter
                    </button>
                    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary" title="Clear Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table Card -->
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-bold">Users List ({{ $users->total() }})</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Name & Username</th>
                            <th>Email & Phone</th>
                            <th>Primary Branch</th>
                            <th>Department</th>
                            <th>Role(s)</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th class="pe-3 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $u)
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-semibold text-dark">{{ $u->name }}</div>
                                    <small class="text-muted font-monospace">@ {{ $u->username }}</small>
                                </td>
                                <td>
                                    <div>{{ $u->email }}</div>
                                    <small class="text-muted">{{ $u->phone ?? '-' }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $u->branch?->name ?? 'Headquarters' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="small text-muted">{{ $u->department?->name ?? 'General' }}</span>
                                </td>
                                <td>
                                    @foreach($u->roles as $r)
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 me-1">{{ $r->name }}</span>
                                    @endforeach
                                </td>
                                <td>
                                    @if($u->is_active)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Inactive</span>
                                    @endif
                                </td>
                                <td class="small text-muted">
                                    {{ $u->last_login_at ? $u->last_login_at->format('M d, Y H:i') : 'Never' }}
                                </td>
                                <td class="pe-3 text-end">
                                    <div class="btn-group btn-group-sm">
                                        @if(auth()->user()->hasPermission('users.edit'))
                                            <a href="{{ route('users.edit', $u) }}" class="btn btn-outline-secondary" title="Edit User">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            @if($u->id !== auth()->id())
                                                <form method="POST" action="{{ route('users.toggle-status', $u) }}" class="d-inline" onsubmit="return confirm('Change active status for {{ $u->name }}?');">
                                                    @csrf
                                                    <button type="submit" class="btn {{ $u->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}" title="{{ $u->is_active ? 'Deactivate' : 'Activate' }}">
                                                        <i class="bi {{ $u->is_active ? 'bi-person-dash' : 'bi-person-check' }}"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No users matched your search criteria.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($users->hasPages())
            <div class="card-footer bg-white py-3">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
