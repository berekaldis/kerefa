@extends('layouts.app')

@section('title', 'Branch Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Restaurant Branches</h4>
            <p class="text-muted small mb-0">Manage operational restaurant branches, currency codes, and location settings.</p>
        </div>
        @if(auth()->user()->hasPermission('branches.manage'))
            <a href="{{ route('branches.create') }}" class="btn btn-kerefa">
                <i class="bi bi-building-add me-1"></i> Register New Branch
            </a>
        @endif
    </div>

    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-bold">Branches Directory</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Code</th>
                            <th>Branch Name</th>
                            <th>Address & Contact</th>
                            <th>Base Currency</th>
                            <th>Departments</th>
                            <th>Staff Assigned</th>
                            <th>Status</th>
                            @if(auth()->user()->hasPermission('branches.manage'))
                            <th class="pe-3 text-end">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($branches as $b)
                            <tr>
                                <td class="ps-3 font-monospace fw-bold">{{ $b->code }}</td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $b->name }}</div>
                                    @if($b->is_main)
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25" style="font-size: 0.7rem;">Main Branch / HQ</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="small">{{ $b->address ?? 'No address' }}</div>
                                    <div class="small text-muted">{{ $b->phone ?? '-' }} | {{ $b->email ?? '-' }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $b->currency }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border">
                                        {{ $b->departments_count }} Departments
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">
                                        {{ $b->users_count }} Staff
                                    </span>
                                </td>
                                <td>
                                    @if($b->is_active)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Inactive</span>
                                    @endif
                                </td>
                                @if(auth()->user()->hasPermission('branches.manage'))
                                <td class="pe-3 text-end">
                                    <a href="{{ route('branches.edit', $b) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-pencil me-1"></i> Edit
                                    </a>
                                </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ auth()->user()->hasPermission('branches.manage') ? 8 : 7 }}" class="text-center py-4 text-muted">No branches found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($branches->hasPages())
            <div class="card-footer bg-white py-3">
                {{ $branches->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
