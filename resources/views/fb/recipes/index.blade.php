@extends('layouts.app')

@section('title', 'Food & Beverage Recipes & Costing')

@section('content')
<div class="container-fluid px-1 px-sm-2 px-md-3">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
        <div>
            <h4 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                <i class="bi bi-book text-warning"></i>
                <span>F&B Recipes & Portion Costing</span>
            </h4>
            <span class="text-muted small">Standardized portion specifications, food cost percentages, and theoretical yield</span>
        </div>
        <div>
            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-2">
                Average Food Cost: <strong class="font-mono">{{ number_format($stats['avg_food_cost'], 1) }}%</strong>
            </span>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-2 g-sm-3 mb-3">
        <div class="col-6 col-md-4">
            <div class="card p-3 border-0 shadow-sm rounded-4 h-100">
                <div class="text-muted small fw-bold text-uppercase">Configured Recipes</div>
                <h3 class="fw-bold text-dark font-mono mt-1 mb-0">{{ $stats['total_recipes'] }}</h3>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card p-3 border-0 shadow-sm rounded-4 h-100">
                <div class="text-muted small fw-bold text-uppercase">Costing Configured</div>
                <h3 class="fw-bold text-primary font-mono mt-1 mb-0">{{ $stats['costing_configured'] }}</h3>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card p-3 border-0 shadow-sm rounded-4 h-100">
                <div class="text-muted small fw-bold text-uppercase">Standard Margin Target</div>
                <h3 class="fw-bold text-success font-mono mt-1 mb-0">65% - 70%</h3>
            </div>
        </div>
    </div>

    <!-- Recipes Grid -->
    <div class="row g-3">
        @forelse($recipes as $recipe)
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-3 bg-white">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h5 class="fw-bold text-dark mb-0">{{ $recipe->name }}</h5>
                            <span class="badge bg-light text-primary border rounded-pill small">
                                Menu: {{ $recipe->menuItem?->name ?? 'Standard' }}
                            </span>
                        </div>
                        <span class="badge bg-success-subtle text-success font-mono">
                            {{ number_format($recipe->food_cost_percentage, 1) }}% Cost
                        </span>
                    </div>

                    <div class="p-2.5 bg-light rounded-3 mb-3 small font-mono">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Portion Cost:</span>
                            <strong>ETB {{ number_format($recipe->cost_per_portion, 2) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Selling Price:</span>
                            <strong class="text-primary">ETB {{ number_format($recipe->menuItem?->price ?? 0, 2) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Gross Margin:</span>
                            <strong class="text-success">{{ number_format(100 - $recipe->food_cost_percentage, 1) }}%</strong>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="fw-bold small text-muted text-uppercase mb-1" style="font-size: 11px;">Key Ingredients</div>
                        <div class="d-flex flex-column gap-1">
                            @foreach($recipe->ingredients as $ing)
                                <div class="d-flex justify-content-between small text-muted">
                                    <span>&bull; {{ $ing->inventoryItem?->name }}</span>
                                    <span class="font-mono">{{ number_format($ing->quantity, 2) }} {{ $ing->unit }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @if($recipe->instructions)
                    <div class="mt-auto pt-2 border-top small text-muted fst-italic">
                        <i class="bi bi-info-circle me-1"></i> {{ Str::limit($recipe->instructions, 90) }}
                    </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5 text-muted">
                No recipes currently configured.
            </div>
        @endforelse
    </div>
</div>
@endsection
