@extends('layouts.app')

@section('title', 'System Settings')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">System Configuration & Policies</h4>
            <p class="text-muted small mb-0">Global restaurant parameters, tax rates, currencies, and operational feature flags.</p>
        </div>
        <span class="badge bg-secondary p-2">
            <i class="bi bi-gear-wide-connected me-1"></i> Global Configuration
        </span>
    </div>

    <!-- Alert regarding Initial Release Policies -->
    <div class="alert alert-warning border-warning border-opacity-50 shadow-sm mb-4">
        <div class="d-flex gap-3">
            <i class="bi bi-info-circle-fill fs-4 text-warning"></i>
            <div>
                <h6 class="fw-bold mb-1">Initial Release Scope Notice</h6>
                <p class="small mb-0">
                    As specified in the Kerefa Restaurant ERP design architecture, <strong>Customer Bill Generation & Receipt Printing is strictly disabled</strong> in this release. The system operates in pure order taking, kitchen/bar dispatch, and preparation workflow tracking mode. Customer billing requires subsequent management and statutory confirmation.
                </p>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('settings.update') }}">
        @csrf
        @method('PUT')

        <div class="row g-4">
            @foreach($groupedSettings as $category => $settings)
                <div class="col-lg-6">
                    <div class="card h-100 shadow-sm border">
                        <div class="card-header bg-white py-3">
                            <h6 class="fw-bold mb-0 text-uppercase small text-dark">
                                <i class="bi {{ $category === 'pos' ? 'bi-grid-3x3-gap-fill text-warning' : 'bi-sliders text-primary' }} me-2"></i>
                                {{ ucfirst($category) }} Configuration
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-column gap-3">
                                @foreach($settings as $s)
                                    <div>
                                        <label for="setting_{{ $s->key }}" class="form-label small fw-semibold">
                                            {{ ucwords(str_replace('_', ' ', $s->key)) }}
                                        </label>

                                        @if($s->key === 'bill_generation_enabled')
                                            <div class="input-group">
                                                <input type="text" class="form-control bg-light text-muted" value="DISABLED (Initial Release Policy)" disabled>
                                                <input type="hidden" name="{{ $s->key }}" value="0">
                                                <span class="input-group-text bg-secondary text-white small"><i class="bi bi-lock-fill"></i></span>
                                            </div>
                                            <div class="form-text text-danger small">
                                                <i class="bi bi-shield-x me-1"></i> Customer bill and receipt printing generation is locked per master specification.
                                            </div>
                                        @elseif($s->type === 'boolean')
                                            <select name="{{ $s->key }}" id="setting_{{ $s->key }}" class="form-select">
                                                <option value="1" {{ $s->value == '1' ? 'selected' : '' }}>Enabled</option>
                                                <option value="0" {{ $s->value == '0' ? 'selected' : '' }}>Disabled</option>
                                            </select>
                                            @if($s->description)
                                                <div class="form-text small">{{ $s->description }}</div>
                                            @endif
                                        @else
                                            <input type="text" class="form-control" id="setting_{{ $s->key }}" name="{{ $s->key }}" value="{{ $s->value }}">
                                            @if($s->description)
                                                <div class="form-text small">{{ $s->description }}</div>
                                            @endif
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="d-flex justify-content-end mt-4 pt-3 border-top">
            <button type="submit" class="btn btn-kerefa px-4 py-2">
                <i class="bi bi-check2-circle me-1"></i> Save System Configurations
            </button>
        </div>
    </form>
</div>
@endsection
