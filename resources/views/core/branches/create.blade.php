@extends('layouts.app')

@section('title', 'Register New Branch')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Register New Restaurant Branch</h4>
            <p class="text-muted small mb-0">Add a new operational location for multi-branch scaling.</p>
        </div>
        <a href="{{ route('branches.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Branches
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

    <div class="card">
        <div class="card-header bg-white py-3">
            <h6 class="fw-bold mb-0"><i class="bi bi-building me-2 text-primary"></i>Branch Information</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('branches.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="code" class="form-label small fw-semibold">Branch Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code') }}" required placeholder="e.g. BOLE, CMC, KAZ">
                        <div class="form-text small">Short unique identifier for orders, stock, and reports.</div>
                    </div>

                    <div class="col-md-8">
                        <label for="name" class="form-label small fw-semibold">Branch Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required placeholder="e.g. Kerefa Restaurant - Bole Branch">
                    </div>

                    <div class="col-md-12">
                        <label for="address" class="form-label small fw-semibold">Physical Address</label>
                        <input type="text" class="form-control @error('address') is-invalid @enderror" id="address" name="address" value="{{ old('address') }}" placeholder="Street, Sub-city, City">
                    </div>

                    <div class="col-md-4">
                        <label for="phone" class="form-label small fw-semibold">Contact Phone</label>
                        <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}">
                    </div>

                    <div class="col-md-4">
                        <label for="email" class="form-label small fw-semibold">Contact Email</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}">
                    </div>

                    <div class="col-md-4">
                        <label for="currency" class="form-label small fw-semibold">Base Currency <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase @error('currency') is-invalid @enderror" id="currency" name="currency" value="{{ old('currency', 'ETB') }}" required>
                    </div>

                    <div class="col-md-6">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="is_main" value="1" id="is_main" {{ old('is_main') ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold small" for="is_main">Designate as Main Branch / HQ</label>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold small" for="is_active">Branch Active</label>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-kerefa px-4">
                        <i class="bi bi-check2-circle me-1"></i> Register Branch
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
