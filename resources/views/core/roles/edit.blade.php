@extends('layouts.app')

@section('title', 'Configure Role Permissions')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Role Permissions: {{ $role->name }}</h4>
            <p class="text-muted small mb-0">Select individual permissions granted to users assigned this role.</p>
        </div>
        <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Roles
        </a>
    </div>

    <form method="POST" action="{{ route('roles.update', $role) }}">
        @csrf
        @method('PUT')

        <div class="card mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-shield-shaded me-2 text-primary"></i>Permission Matrix by Module</span>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="toggleAllBtn">
                    Toggle All
                </button>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    @php
                        $assignedIds = $role->permissions->pluck('id')->toArray();
                    @endphp
                    @foreach($groupedPermissions as $module => $perms)
                        <div class="col-md-6 col-xl-4">
                            <div class="border rounded p-3 bg-light h-100">
                                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                    <h6 class="fw-bold mb-0 text-uppercase small text-dark">
                                        <i class="bi bi-folder2-open me-1 text-secondary"></i> {{ ucfirst($module) }}
                                    </h6>
                                    <span class="badge bg-secondary">{{ count($perms) }}</span>
                                </div>

                                <div class="d-flex flex-column gap-2">
                                    @foreach($perms as $p)
                                        <div class="form-check">
                                            <input class="form-check-input perm-checkbox" type="checkbox" name="permissions[]" value="{{ $p->id }}" id="perm_{{ $p->id }}" {{ in_array($p->id, $assignedIds) ? 'checked' : '' }}>
                                            <label class="form-check-label small" for="perm_{{ $p->id }}">
                                                <span class="fw-semibold text-dark">{{ $p->name }}</span>
                                                <div class="text-muted" style="font-size: 0.72rem;">{{ $p->description }}</div>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="card-footer bg-white py-3 d-flex justify-content-end">
                <button type="submit" class="btn btn-kerefa px-4">
                    <i class="bi bi-check2-circle me-1"></i> Save Role Permissions
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    document.getElementById('toggleAllBtn')?.addEventListener('click', function () {
        const checkboxes = document.querySelectorAll('.perm-checkbox');
        const anyUnchecked = Array.from(checkboxes).some(cb => !cb.checked);
        checkboxes.forEach(cb => cb.checked = anyUnchecked);
    });
</script>
@endpush
@endsection
