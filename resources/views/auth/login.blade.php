@extends('layouts.auth')

@section('content')
<div class="auth-card">
    <div class="auth-header">
        <div class="brand-badge">
            <i class="bi bi-cup-hot-fill"></i>
        </div>
        <h4 class="fw-bold mb-1 text-white">KEREFA RESTAURANT</h4>
        <p class="text-secondary small mb-0">Integrated Restaurant ERP System</p>
    </div>

    <div class="p-4">
        @if (session('info'))
            <div class="alert alert-info py-2 px-3 small border-0 mb-3">
                <i class="bi bi-info-circle me-1"></i> {{ session('info') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger py-2 px-3 small border-0 mb-3">
                <i class="bi bi-exclamation-triangle me-1"></i>
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}">
            @csrf

            <div class="mb-3">
                <label for="login" class="form-label small text-secondary">Username or Email</label>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary text-secondary"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control @error('login') is-invalid @enderror" id="login" name="login" value="{{ old('login') }}" required autofocus placeholder="admin or admin@kerefa.com">
                </div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label small text-secondary">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary text-secondary"><i class="bi bi-key"></i></span>
                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required placeholder="••••••••">
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-check-label small text-secondary" for="remember">
                        Remember me
                    </label>
                </div>
            </div>

            <button type="submit" class="btn btn-kerefa w-100 mb-3">
                <i class="bi bi-box-arrow-in-right me-1"></i> Sign In to ERP
            </button>
        </form>

        <div class="pt-3 border-top border-secondary border-opacity-25 text-center">
            <span class="badge bg-secondary bg-opacity-25 text-secondary px-2 py-1 small">
                <i class="bi bi-shield-lock me-1"></i> Role-Based Access Control
            </span>
            <div class="mt-2 text-muted" style="font-size: 0.75rem;">
                Default Admin: <code>admin@kerefa.com</code> / <code>Admin1234!</code>
            </div>
        </div>
    </div>
</div>
@endsection
