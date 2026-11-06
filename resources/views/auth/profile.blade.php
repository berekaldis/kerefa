@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 fw-bold"><i class="bi bi-person-circle me-2 text-primary"></i>User Account Profile</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Full Name</div>
                        <div class="col-sm-8 fw-semibold">{{ $user->name }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Username</div>
                        <div class="col-sm-8"><code>{{ $user->username }}</code></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Email Address</div>
                        <div class="col-sm-8">{{ $user->email }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Phone Number</div>
                        <div class="col-sm-8">{{ $user->phone ?? 'Not provided' }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Primary Branch</div>
                        <div class="col-sm-8">
                            <span class="badge bg-secondary">{{ $user->branch?->name ?? 'Head Office / Global' }}</span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Assigned Roles</div>
                        <div class="col-sm-8">
                            @foreach($user->roles as $role)
                                <span class="badge bg-primary me-1">{{ $role->name }}</span>
                            @endforeach
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Last Login</div>
                        <div class="col-sm-8 small text-muted">
                            {{ $user->last_login_at ? $user->last_login_at->format('M d, Y h:i A') . ' (IP: ' . $user->last_login_ip . ')' : 'First session' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Password Change Card -->
            <div class="card">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 fw-bold"><i class="bi bi-shield-lock me-2 text-warning"></i>Change Password</h5>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger py-2 small mb-3">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('profile.password') }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="current_password" class="form-label small fw-semibold">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label small fw-semibold">New Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="form-text small">Must be at least 8 characters with letters, numbers, and symbols.</div>
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label small fw-semibold">Confirm New Password</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check2-circle me-1"></i> Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
