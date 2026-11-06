@extends('layouts.auth')

@section('content')
<div class="auth-card">
    <div class="auth-header">
        <div class="brand-badge">
            <i class="bi bi-cup-hot-fill"></i>
        </div>
        <h4 class="fw-bold mb-1 text-white" style="letter-spacing: 0.5px;">KEREFA RESTAURANT</h4>
        <p class="text-white-50 small mb-0 fw-medium">Integrated Restaurant ERP System</p>
    </div>

    <div class="p-4">
        @if (session('info'))
            <div class="alert alert-info py-2 px-3 small border-0 mb-3 rounded-3 d-flex align-items-center">
                <i class="bi bi-info-circle me-2 fs-5"></i>
                <div>{{ session('info') }}</div>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger py-2 px-3 small border-0 mb-3 rounded-3">
                <div class="d-flex align-items-center mb-1">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Authentication Failed</strong>
                </div>
                @foreach ($errors->all() as $error)
                    <div class="ps-3">&bull; {{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}">
            @csrf

            <div class="mb-3">
                <label for="login" class="form-label small text-white-50 fw-semibold">Username or Email</label>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary border-opacity-50 text-secondary"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control @error('login') is-invalid @enderror" id="login" name="login" value="{{ old('login', 'admin@kerefa.com') }}" required autofocus placeholder="admin or admin@kerefa.com">
                </div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label small text-white-50 fw-semibold">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary border-opacity-50 text-secondary"><i class="bi bi-shield-lock"></i></span>
                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" value="Admin1234!" required placeholder="••••••••">
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember" checked>
                    <label class="form-check-label small text-white-50" for="remember">
                        Remember session
                    </label>
                </div>
            </div>

            <button type="submit" class="btn btn-kerefa w-100 mb-3">
                <i class="bi bi-box-arrow-in-right me-2"></i> Sign In to ERP
            </button>
        </form>

        <!-- Quick Demo Switcher -->
        <div class="pt-3 border-top border-white border-opacity-10 text-center">
            <span class="text-white-50 small fw-semibold d-block mb-2">Quick Demo Accounts:</span>
            <div class="d-flex flex-wrap gap-1 justify-content-center">
                <button type="button" class="btn btn-sm btn-dark border border-secondary border-opacity-25 text-white-50 px-2 py-1" style="font-size: 0.72rem;" onclick="setAccount('admin@kerefa.com', 'Admin1234!')">
                    <i class="bi bi-shield-fill text-warning me-1"></i> Admin
                </button>
                <button type="button" class="btn btn-sm btn-dark border border-secondary border-opacity-25 text-white-50 px-2 py-1" style="font-size: 0.72rem;" onclick="setAccount('waiter@kerefa.com', 'Password123!')">
                    <i class="bi bi-person-fill text-info me-1"></i> Waiter
                </button>
                <button type="button" class="btn btn-sm btn-dark border border-secondary border-opacity-25 text-white-50 px-2 py-1" style="font-size: 0.72rem;" onclick="setAccount('kitchen@kerefa.com', 'Password123!')">
                    <i class="bi bi-fire text-danger me-1"></i> Kitchen
                </button>
                <button type="button" class="btn btn-sm btn-dark border border-secondary border-opacity-25 text-white-50 px-2 py-1" style="font-size: 0.72rem;" onclick="setAccount('bar@kerefa.com', 'Password123!')">
                    <i class="bi bi-cup-straw text-primary me-1"></i> Bar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function setAccount(login, pass) {
        document.getElementById('login').value = login;
        document.getElementById('password').value = pass;
    }
</script>
@endsection
