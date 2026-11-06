@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Edit User: {{ $user->name }}</h4>
            <p class="text-muted small mb-0">Modify account credentials, role permissions, and branch assignments.</p>
        </div>
        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Users
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger shadow-sm mb-4">
            <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Please resolve the following errors:</div>
            <ul class="mb-0 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('users.update', $user) }}">
        @csrf
        @method('PUT')
        <div class="row g-4">
            <!-- Account Information -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-person-lines-fill me-2 text-primary"></i>Basic Profile Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label small fw-semibold">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                            </div>

                            <div class="col-md-6">
                                <label for="username" class="form-label small fw-semibold">Username <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username', $user->username) }}" required>
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label small fw-semibold">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                            </div>

                            <div class="col-md-6">
                                <label for="phone" class="form-label small fw-semibold">Phone Number</label>
                                <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $user->phone) }}">
                            </div>

                            <div class="col-md-12">
                                <label for="password" class="form-label small fw-semibold">Reset Password</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="Leave blank to keep existing password">
                                <div class="form-text small">Leave blank to retain current user password.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Multi-Branch Access -->
                <div class="card">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-diagram-3 me-2 text-primary"></i>Authorized Branch Access</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">Select all branches this user is authorized to switch into and access:</p>
                        <div class="row g-2">
                            @php
                                $assignedBranchIds = old('accessible_branches', $user->accessibleBranches->pluck('id')->toArray());
                            @endphp
                            @foreach($branches as $b)
                                <div class="col-md-6">
                                    <div class="form-check p-2 border rounded">
                                        <input class="form-check-input ms-0 me-2" type="checkbox" name="accessible_branches[]" value="{{ $b->id }}" id="branch_{{ $b->id }}" {{ in_array($b->id, $assignedBranchIds) ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold small" for="branch_{{ $b->id }}">
                                            {{ $b->name }} <span class="badge bg-light text-secondary border ms-1">{{ $b->code }}</span>
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Role & Organization Sidebar -->
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-building me-2 text-primary"></i>Branch & Department</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="branch_id" class="form-label small fw-semibold">Primary Branch <span class="text-danger">*</span></label>
                            <select name="branch_id" id="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                                <option value="">Select Primary Branch...</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}" {{ old('branch_id', $user->branch_id) == $b->id ? 'selected' : '' }}>
                                        {{ $b->name }} ({{ $b->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="department_id" class="form-label small fw-semibold">Department</label>
                            <select name="department_id" id="department_id" class="form-select @error('department_id') is-invalid @enderror">
                                <option value="">Select Department...</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id', $user->department_id) == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }} ({{ $dept->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-check form-switch mt-3">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $user->is_active ? '1' : '0') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold small" for="is_active">Account Active</label>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-shield-check me-2 text-primary"></i>Role Assignment <span class="text-danger">*</span></h6>
                    </div>
                    <div class="card-body">
                        @php
                            $userRoleIds = old('roles', $user->roles->pluck('id')->toArray());
                        @endphp
                        <div class="d-flex flex-column gap-2" style="max-height: 280px; overflow-y: auto;">
                            @foreach($roles as $r)
                                <div class="form-check p-2 border rounded">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="roles[]" value="{{ $r->id }}" id="role_{{ $r->id }}" {{ in_array($r->id, $userRoleIds) ? 'checked' : '' }}>
                                    <label class="form-check-label small fw-semibold" for="role_{{ $r->id }}">
                                        {{ $r->name }}
                                        <div class="text-muted" style="font-size: 0.72rem;">{{ $r->description }}</div>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-kerefa w-100 py-2">
                    <i class="bi bi-check2-circle me-1"></i> Update User Account
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
