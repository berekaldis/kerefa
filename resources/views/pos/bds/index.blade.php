@extends('layouts.app')

@section('title', 'Bar Display System (BDS)')

@push('styles')
<meta http-equiv="refresh" content="30">
<style>
    .bds-card {
        border-radius: 16px;
        transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.2s ease;
        border-width: 2px;
        background: #ffffff;
    }
    .bds-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 28px -6px rgba(15, 23, 42, 0.12);
    }
    .bds-card-queued {
        border-color: #f59e0b;
        background-color: #fffdf5;
    }
    .bds-card-preparing {
        border-color: #0284c7;
        background-color: #f0f9ff;
    }
    .bds-card-ready {
        border-color: #10b981;
        background-color: #f0fdf4;
    }
    .filter-pill.active {
        background-color: #0f172a !important;
        color: #fff !important;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-1 px-sm-2 px-md-3">
    <!-- Header with Live Counters and Controls -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-info bg-opacity-25 text-info rounded-circle p-2"><i class="bi bi-cup-straw fs-5"></i></span>
                <div>
                    <h4 class="fw-bold mb-0 text-dark">Bar & Beverage Display (BDS)</h4>
                    <span class="text-muted small">Live beverage, juice & coffee queue &bull; Bar Station</span>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <!-- Filter Pills -->
            <div class="btn-group btn-group-sm rounded-pill border bg-white p-1 shadow-sm" role="group">
                <button type="button" class="btn btn-sm rounded-pill filter-pill active fw-bold px-3" onclick="filterBds('all')">
                    All <span class="badge bg-secondary rounded-pill ms-1">{{ count($tickets) }}</span>
                </button>
                <button type="button" class="btn btn-sm rounded-pill filter-pill fw-bold px-3 text-warning" onclick="filterBds('queued')">
                    Queued <span class="badge bg-warning text-dark rounded-pill ms-1">{{ $tickets->where('status', 'queued')->count() }}</span>
                </button>
                <button type="button" class="btn btn-sm rounded-pill filter-pill fw-bold px-3 text-info" onclick="filterBds('preparing')">
                    Pouring <span class="badge bg-info text-dark rounded-pill ms-1">{{ $tickets->where('status', 'preparing')->count() }}</span>
                </button>
                <button type="button" class="btn btn-sm rounded-pill filter-pill fw-bold px-3 text-success" onclick="filterBds('ready')">
                    Ready <span class="badge bg-success rounded-pill ms-1">{{ $tickets->where('status', 'ready')->count() }}</span>
                </button>
            </div>

            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill shadow-sm" id="soundToggleBtn" onclick="toggleBdsSound()">
                <i class="bi bi-volume-up-fill text-warning me-1"></i> Chime On
            </button>
            <a href="{{ route('bds.index') }}" class="btn btn-sm btn-light border rounded-pill shadow-sm px-3" title="Refresh Tickets">
                <i class="bi bi-arrow-clockwise me-1"></i> 30s
            </a>
        </div>
    </div>

    <!-- Active Tickets Grid -->
    <div class="row g-3" id="bdsTicketsGrid">
        @forelse($tickets as $ticket)
            @php
                $statusClass = match($ticket->status) {
                    'queued' => 'bds-card-queued',
                    'preparing' => 'bds-card-preparing',
                    'ready' => 'bds-card-ready',
                    default => 'bg-white',
                };
                $badgeBg = match($ticket->status) {
                    'queued' => 'bg-warning text-dark',
                    'preparing' => 'bg-info text-dark',
                    'ready' => 'bg-success text-white',
                    default => 'bg-secondary',
                };
            @endphp
            <div class="col-12 col-md-6 col-xl-4 bds-ticket-item" data-status="{{ $ticket->status }}">
                <div class="card h-100 bds-card {{ $statusClass }} shadow-sm">
                    <!-- Ticket Header -->
                    <div class="card-header bg-white bg-opacity-75 py-2 px-3 d-flex justify-content-between align-items-center border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-dark font-mono rounded-pill px-2 py-1">{{ $ticket->ticket_number }}</span>
                            <span class="fw-bold text-dark font-mono">{{ $ticket->order?->order_number }}</span>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <button type="button" class="btn btn-sm btn-light border rounded-circle p-1" title="Print BOT to IRP 200D" onclick="printBdsTicketDirect({{ $ticket->id }})">
                                <i class="bi bi-printer text-dark"></i>
                            </button>
                            <span class="badge {{ $badgeBg }} text-uppercase rounded-pill px-2 py-1" style="font-size: 0.68rem;">
                                {{ $ticket->status }}
                            </span>
                        </div>
                    </div>

                    <!-- Ticket Target & Server Meta -->
                    <div class="px-3 py-2 bg-white bg-opacity-50 border-bottom d-flex justify-content-between align-items-center small text-muted">
                        <div>
                            @if($ticket->order?->isDineIn())
                                <strong class="text-info fs-6"><i class="bi bi-geo-alt-fill me-1"></i>Table {{ $ticket->order?->table?->table_number ?? 'N/A' }}</strong>
                                <span class="text-muted" style="font-size: 0.72rem;">({{ $ticket->order?->table?->diningArea?->name ?? 'Floor' }})</span>
                            @else
                                <strong class="text-primary text-uppercase fs-6"><i class="bi bi-bag-fill me-1"></i>{{ str_replace('_', ' ', $ticket->order?->order_type) }}</strong>
                            @endif
                        </div>
                        <div class="text-end">
                            <div class="fw-semibold text-dark" style="font-size: 0.75rem;">Server: {{ $ticket->order?->waiter?->name ?? 'Staff' }}</div>
                            <span class="font-mono text-muted" style="font-size: 0.7rem;"><i class="bi bi-clock me-1"></i>{{ $ticket->sent_at->diffForHumans(null, true) }}</span>
                        </div>
                    </div>

                    <!-- Ticket Drink Items List -->
                    <div class="card-body p-3">
                        <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                            @foreach($ticket->items as $tItem)
                                <li class="p-2 border rounded-3 bg-white d-flex justify-content-between align-items-start shadow-2xs">
                                    <div>
                                        <div class="fw-bold text-dark fs-6 d-flex align-items-center gap-2">
                                            <span class="badge bg-info text-dark font-mono rounded-pill px-2 py-1" style="font-size: 0.85rem;">
                                                {{ (float)$tItem->quantity }}x
                                            </span>
                                            <span>{{ $tItem->orderItem?->menuItem?->name }}</span>
                                        </div>
                                        @if($tItem->special_instructions)
                                            <div class="badge bg-info bg-opacity-10 text-dark border border-info border-opacity-25 mt-1 small text-wrap text-start">
                                                <i class="bi bi-chat-dots-fill me-1"></i> {{ $tItem->special_instructions }}
                                            </div>
                                        @endif
                                    </div>
                                    <span class="badge bg-light text-muted border text-uppercase" style="font-size: 0.65rem;">
                                        {{ $tItem->status }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>

                        @if($ticket->order?->notes)
                            <div class="mt-2 p-2 bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded-3 small">
                                <strong class="text-warning-emphasis"><i class="bi bi-info-circle-fill me-1"></i>Order Note:</strong> {{ $ticket->order->notes }}
                            </div>
                        @endif
                    </div>

                    <!-- Ticket Action Buttons -->
                    <div class="card-footer bg-white py-2 px-3 border-top d-flex gap-2">
                        <!-- Direct IRP 200D Printer Button -->
                        <button type="button" class="btn btn-sm btn-outline-dark rounded-pill px-3 font-mono" title="Print to IRP 200D Printer" onclick="printBdsTicketDirect({{ $ticket->id }})">
                            <i class="bi bi-printer-fill text-warning me-1"></i> Print BOT
                        </button>

                        @if($ticket->status === 'queued')
                            <form method="POST" action="{{ route('bds.ticket.status', $ticket) }}" class="flex-grow-1">
                                @csrf
                                <input type="hidden" name="status" value="preparing">
                                <button type="submit" class="btn btn-sm btn-info w-100 fw-bold rounded-pill shadow-sm">
                                    <i class="bi bi-play-fill me-1"></i> Start Pouring / Brewing
                                </button>
                            </form>
                        @elseif($ticket->status === 'preparing')
                            <form method="POST" action="{{ route('bds.ticket.status', $ticket) }}" class="flex-grow-1">
                                @csrf
                                <input type="hidden" name="status" value="ready">
                                <button type="submit" class="btn btn-sm btn-success w-100 fw-bold rounded-pill shadow-sm">
                                    <i class="bi bi-check-lg me-1"></i> Drinks Ready
                                </button>
                            </form>
                        @elseif($ticket->status === 'ready')
                            <form method="POST" action="{{ route('bds.ticket.status', $ticket) }}" class="flex-grow-1">
                                @csrf
                                <input type="hidden" name="status" value="collected">
                                <button type="submit" class="btn btn-sm btn-secondary w-100 rounded-pill">
                                    <i class="bi bi-hand-thumbs-up me-1"></i> Drinks Collected
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Hidden Raw Printable Data for IRP 200D -->
            <div id="botPrintData_{{ $ticket->id }}" style="display: none;">
                <div class="irp200d-ticket-header">
                    <div class="irp200d-title">KEREFA RESTAURANT</div>
                    <div style="font-size: 10px; margin-top: 1px;">Thermal Order Printer - IRP 200D</div>
                    <div class="irp200d-banner">*** BAR ORDER TICKET (BOT) ***</div>
                </div>
                <div class="irp200d-meta-row">
                    <span><strong>TICKET :</strong> {{ $ticket->ticket_number }}</span>
                    <span><strong>ORDER :</strong> {{ $ticket->order?->order_number }}</span>
                </div>
                <div class="irp200d-meta-row">
                    <span><strong>TARGET :</strong> {{ $ticket->order?->isDineIn() ? 'TABLE ' . ($ticket->order?->table?->table_number ?? 'N/A') . ' (' . ($ticket->order?->table?->diningArea?->name ?? 'Floor') . ')' : strtoupper($ticket->order?->order_type) }}</span>
                </div>
                <div class="irp200d-meta-row">
                    <span><strong>WAITER :</strong> {{ $ticket->order?->waiter?->name ?? 'Staff' }}</span>
                    <span>{{ now()->format('Y-m-d H:i') }}</span>
                </div>
                @if($ticket->order?->notes)
                <div style="font-size: 11px; margin-top: 2px;"><strong>ORDER NOTE:</strong> {{ $ticket->order->notes }}</div>
                @endif
                <table class="irp200d-items-table">
                    <thead>
                        <tr>
                            <th style="width: 32px;">QTY</th>
                            <th>DRINK / PREPARATION NOTE</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $botCount = 0; @endphp
                        @foreach($ticket->items as $tItem)
                            @php $botCount += (float)$tItem->quantity; @endphp
                            <tr>
                                <td class="irp200d-item-qty">[ {{ (float)$tItem->quantity }} ]</td>
                                <td class="irp200d-item-name">
                                    {{ $tItem->orderItem?->menuItem?->name }}
                                    @if($tItem->special_instructions)
                                        <div class="irp200d-item-notes">>> Note: {{ $tItem->special_instructions }}</div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="irp200d-meta-row" style="font-size: 12px; font-weight: bold;">
                    <span>TOTAL BEVERAGES:</span>
                    <span>{{ $botCount }} DRINKS</span>
                </div>
                <div class="irp200d-cutter-feed">
                    - - - - - - - - - - - - - [ IRP 200D CUT LINE ] - - - - - - - - - - - - -
                </div>
            </div>
        @empty
            <div class="col-12 py-5 text-center text-muted">
                <i class="bi bi-cup-hot fs-1 text-info"></i>
                <h5 class="fw-bold mt-2 text-dark">Bar Queue is Clear</h5>
                <p class="small text-muted mb-0">No active beverage or coffee preparation tickets at this time.</p>
            </div>
        @endforelse
    </div>
</div>

@push('scripts')
<script>
    // Ticket Filter Switching
    function filterBds(status) {
        document.querySelectorAll('.filter-pill').forEach(btn => btn.classList.remove('active'));
        event.currentTarget.classList.add('active');

        document.querySelectorAll('.bds-ticket-item').forEach(card => {
            if (status === 'all' || card.getAttribute('data-status') === status) {
                card.classList.remove('d-none');
            } else {
                card.classList.add('d-none');
            }
        });
    }

    // Direct Print Trigger for IRP 200D
    function printBdsTicketDirect(ticketId) {
        const rawHtml = document.getElementById('botPrintData_' + ticketId)?.innerHTML;
        if (rawHtml) {
            window.printIrp200dTicket(rawHtml);
        }
    }

    // Audio Chime on Tickets
    let soundEnabled = true;
    function toggleBdsSound() {
        soundEnabled = !soundEnabled;
        const btn = document.getElementById('soundToggleBtn');
        if (soundEnabled) {
            btn.innerHTML = '<i class="bi bi-volume-up-fill text-warning me-1"></i> Chime On';
        } else {
            btn.innerHTML = '<i class="bi bi-volume-mute-fill text-secondary me-1"></i> Chime Muted';
        }
    }

    function playBarChime() {
        if (!soundEnabled) return;
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.type = 'sine';
            osc.frequency.setValueAtTime(659.25, ctx.currentTime); // E5
            osc.frequency.setValueAtTime(987.77, ctx.currentTime + 0.1); // B5
            gain.gain.setValueAtTime(0.3, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.35);
            osc.start();
            osc.stop(ctx.currentTime + 0.35);
        } catch(e) {}
    }

    // Trigger chime on initial load if tickets exist
    @if($tickets->where('status', 'queued')->count() > 0)
        setTimeout(playBarChime, 600);
    @endif
</script>
@endpush
@endsection
