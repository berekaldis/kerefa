@extends('layouts.app')

@section('title', 'Roles & Permissions')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Roles & Access Control (RBAC)</h4>
            <p class="text-muted small mb-0">System roles define access boundaries across all restaurant ERP business modules.</p>
        </div>
        <span class="badge bg-secondary p-2">
            <i class="bi bi-shield-lock-fill me-1"></i> 12 System Roles Standardized
        </span>
    </div>

    <div class="row g-3">
        @foreach($roles as $role)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm border">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">{{ $role->name }}</h6>
                            <code class="small text-muted">{{ $role->slug }}</code>
                        </div>
                        @if($role->is_system)
                            <span class="badge bg-dark-subtle text-dark border">System Role</span>
                        @endif
                    </div>
                    <div class="card-body d-flex flex-column justify-content-between">
                        <p class="text-muted small mb-3">{{ $role->description ?? 'No description provided.' }}</p>

                        <div class="pt-2 border-top d-flex justify-content-between align-items-center">
                            <div class="small">
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 me-1">
                                    <i class="bi bi-people me-1"></i> {{ $role->users_count }} Staff
                                </span>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border me-1">
                                    <i class="bi bi-key me-1"></i> {{ $role->permissions_count }} Perms
                                </span>
                            </div>

                            @if(auth()->user()->hasPermission('roles.manage'))
                                <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-shield-check me-1"></i> Permissions
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
