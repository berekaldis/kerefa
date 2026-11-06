@extends('layouts.app')

@section('title', 'Order ' . $order->order_number)

@section('content')
<div class="container-fluid px-1 px-sm-2 px-md-3">
    <!-- Header with Back & Actions -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
        <div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <h4 class="fw-bold mb-0 font-mono">{{ $order->order_number }}</h4>
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 text-uppercase rounded-pill px-2 py-1">
                    {{ str_replace('_', ' ', $order->order_type) }}
                </span>
                @php
                    $badgeClass = match($order->status) {
                        'in_preparation' => 'bg-warning-subtle text-warning border-warning-subtle',
                        'partially_ready' => 'bg-info-subtle text-info border-info-subtle',
                        'ready' => 'bg-success-subtle text-success border-success-subtle',
                        'served' => 'bg-primary-subtle text-primary border-primary-subtle',
                        'completed' => 'bg-secondary-subtle text-secondary border-secondary-subtle',
                        'cancelled' => 'bg-danger-subtle text-danger border-danger-subtle',
                        default => 'bg-light text-dark',
                    };
                @endphp
                <span class="badge {{ $badgeClass }} border text-uppercase rounded-pill px-2 py-1">
                    {{ str_replace('_', ' ', $order->status) }}
                </span>
            </div>
            <p class="text-muted small mb-0 mt-1">
                Placed on {{ $order->created_at->format('M d, Y h:i A') }} &bull; Server: <strong class="text-dark">{{ $order->waiter?->name ?? 'Staff' }}</strong>
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('orders.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Back to Orders
            </a>
            @if($order->isOpen() && auth()->user()->hasPermission('pos.orders.cancel'))
                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#cancelOrderModal">
                    <i class="bi bi-x-circle me-1"></i> Cancel Order
                </button>
            @endif
        </div>
    </div>

    <!-- Scope Notice -->
    <div class="alert alert-light border py-2 px-3 small rounded-3 mb-3 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-1">
        <div>
            <i class="bi bi-info-circle-fill me-1 text-primary"></i> <strong>Initial Release Mode:</strong> Table management, order tracking & kitchen/bar preparation workflow only. Customer billing & checkout invoice generation is not active.
        </div>
        <span class="badge bg-secondary rounded-pill font-mono align-self-start align-self-sm-auto">Customer Bill Deferred</span>
    </div>

    <div class="row g-3">
        <!-- Order Items & Tickets -->
        <div class="col-lg-8">
            <!-- Kitchen / Bar Tickets Section -->
            <div class="card mb-3">
                <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <span class="pulse-dot"></span>
                        <h6 class="fw-bold mb-0"><i class="bi bi-receipt-cutoff me-2 text-primary"></i>Department Preparation Tickets</h6>
                    </div>
                    <span class="badge bg-secondary font-mono rounded-pill">{{ count($order->tickets) }} Tickets</span>
                </div>
                <div class="card-body p-3">
                    @forelse($order->tickets as $ticket)
                        <div class="border rounded-3 p-3 mb-3 {{ $ticket->department === 'kitchen' ? 'border-danger-subtle bg-danger bg-opacity-10' : 'border-info-subtle bg-info bg-opacity-10' }}">
                            <div class="d-flex flex-wrap justify-content-between align-items-center pb-2 border-bottom mb-2 gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge {{ $ticket->department === 'kitchen' ? 'bg-danger' : 'bg-info text-dark' }} text-uppercase rounded-pill px-2 py-1">
                                        <i class="bi {{ $ticket->department === 'kitchen' ? 'bi-fire' : 'bi-cup-straw' }} me-1"></i>
                                        {{ $ticket->department }} Ticket
                                    </span>
                                    <strong class="font-mono text-dark">{{ $ticket->ticket_number }}</strong>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <!-- Print to IRP 200D Button -->
                                    <button type="button" class="btn btn-sm btn-white border bg-white shadow-2xs font-mono rounded-pill px-3 py-1" onclick="printOrderTicketDirect({{ $ticket->id }})">
                                        <i class="bi bi-printer-fill text-warning me-1"></i> Print {{ $ticket->department === 'kitchen' ? 'KOT' : 'BOT' }} (IRP 200D)
                                    </button>
                                    <span class="badge bg-white text-dark border text-uppercase rounded-pill px-2 py-1 font-mono" style="font-size: 0.7rem;">
                                        {{ $ticket->status }}
                                    </span>
                                </div>
                            </div>

                            <ul class="list-group list-group-flush bg-transparent">
                                @foreach($ticket->items as $tItem)
                                    <li class="list-group-item bg-transparent px-0 py-2 d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="badge bg-dark rounded-pill me-2 font-mono">{{ (float)$tItem->quantity }}x</span>
                                            <strong class="text-dark">{{ $tItem->orderItem?->menuItem?->name }}</strong>
                                            @if($tItem->special_instructions)
                                                <div class="small text-danger ms-4"><i class="bi bi-chat-left-dots me-1"></i> Note: {{ $tItem->special_instructions }}</div>
                                            @endif
                                        </div>
                                        <span class="badge bg-light text-muted border text-uppercase" style="font-size: 0.65rem;">
                                            {{ $tItem->status }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>

                            <!-- Hidden Print Markup for IRP 200D -->
                            <div id="ticketPrintData_{{ $ticket->id }}" style="display: none;">
                                <div class="irp200d-ticket-header">
                                    <div class="irp200d-title">KEREFA RESTAURANT</div>
                                    <div style="font-size: 10px; margin-top: 1px;">Thermal Order Printer - IRP 200D</div>
                                    <div class="irp200d-banner">*** {{ strtoupper($ticket->department) }} ORDER TICKET ({{ $ticket->department === 'kitchen' ? 'KOT' : 'BOT' }}) ***</div>
                                </div>
                                <div class="irp200d-meta-row">
                                    <span><strong>TICKET :</strong> {{ $ticket->ticket_number }}</span>
                                    <span><strong>ORDER :</strong> {{ $order->order_number }}</span>
                                </div>
                                <div class="irp200d-meta-row">
                                    <span><strong>TARGET :</strong> {{ $order->isDineIn() ? 'TABLE ' . ($order->table?->table_number ?? 'N/A') . ' (' . ($order->table?->diningArea?->name ?? 'Floor') . ')' : strtoupper($order->order_type) }}</span>
                                </div>
                                <div class="irp200d-meta-row">
                                    <span><strong>WAITER :</strong> {{ $order->waiter?->name ?? 'Staff' }}</span>
                                    <span>{{ now()->format('Y-m-d H:i') }}</span>
                                </div>
                                @if($order->notes)
                                <div style="font-size: 11px; margin-top: 2px;"><strong>ORDER NOTE:</strong> {{ $order->notes }}</div>
                                @endif
                                <table class="irp200d-items-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 32px;">QTY</th>
                                            <th>ITEM / SPECIAL INSTRUCTIONS</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $ticketItemsCount = 0; @endphp
                                        @foreach($ticket->items as $tItem)
                                            @php $ticketItemsCount += (float)$tItem->quantity; @endphp
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
                                    <span>TOTAL ITEMS:</span>
                                    <span>{{ $ticketItemsCount }} DISPATCHED</span>
                                </div>
                                <div class="irp200d-cutter-feed">
                                    - - - - - - - - - - - - - [ IRP 200D CUT LINE ] - - - - - - - - - - - - -
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-3">No separate preparation tickets found.</div>
                    @endforelse
                </div>
            </div>

            <!-- Ordered Items Breakdown Table -->
            <div class="card mb-3">
                <div class="card-header bg-white py-2 px-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-card-checklist me-2 text-primary"></i>Ordered Items Summary</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Item Name</th>
                                    <th>Dept</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="pe-3 text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $item)
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-semibold text-dark">{{ $item->menuItem?->name }}</div>
                                            @if($item->special_instructions)
                                                <small class="text-muted"><i class="bi bi-info-circle me-1"></i>{{ $item->special_instructions }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $item->preparation_department === 'bar' ? 'bg-info bg-opacity-25 text-info' : 'bg-danger bg-opacity-25 text-danger' }} rounded-pill" style="font-size: 0.68rem;">
                                                {{ ucfirst($item->preparation_department) }}
                                            </span>
                                        </td>
                                        <td class="text-center fw-bold font-mono">{{ (float)$item->quantity }}</td>
                                        <td class="text-end font-mono">{{ number_format($item->unit_price, 2) }} ETB</td>
                                        <td class="pe-3 text-end fw-semibold text-dark font-mono">{{ number_format($item->subtotal, 2) }} ETB</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Location & Financial Breakdown -->
        <div class="col-lg-4">
            <!-- Table & Destination Card -->
            <div class="card mb-3">
                <div class="card-header bg-white py-2 px-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-geo-alt me-2 text-primary"></i>Service & Location</h6>
                </div>
                <div class="card-body p-3">
                    @if($order->isDineIn())
                        <div class="mb-2">
                            <span class="text-muted small">Dining Floor:</span>
                            <div class="fw-semibold text-dark">{{ $order->table?->diningArea?->name ?? 'Main Floor' }}</div>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted small">Table Number:</span>
                            <div class="fw-bold fs-5 text-primary font-mono">Table {{ $order->table?->table_number ?? 'N/A' }}</div>
                        </div>
                    @elseif($order->isDelivery())
                        <div class="mb-2">
                            <span class="text-muted small">Guest Name:</span>
                            <div class="fw-semibold text-dark">{{ $order->customer_name ?? 'Guest' }} ({{ $order->customer_phone ?? 'N/A' }})</div>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted small">Delivery Address:</span>
                            <div class="fw-semibold text-dark">{{ $order->delivery_address ?? 'Not specified' }}</div>
                        </div>
                    @else
                        <div class="mb-2">
                            <span class="text-muted small">Order Type:</span>
                            <div class="fw-bold fs-5 text-dark">Takeaway Counter</div>
                        </div>
                    @endif

                    @if($order->notes)
                        <div class="mt-2 pt-2 border-top">
                            <span class="text-muted small">Order Note:</span>
                            <div class="small fst-italic text-dark">{{ $order->notes }}</div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Financial Pricing Card -->
            <div class="card mb-3">
                <div class="card-header bg-white py-2 px-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-calculator me-2 text-primary"></i>Pricing Summary</h6>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between text-muted small mb-2">
                        <span>Items Subtotal:</span>
                        <span class="font-mono fw-semibold text-dark">{{ number_format($order->subtotal, 2) }} ETB</span>
                    </div>

                    @if($order->isDineIn())
                        <div class="d-flex justify-content-between text-muted small mb-2">
                            <span>Service Charge (10%):</span>
                            <span class="font-mono fw-semibold text-dark">{{ number_format($order->service_charge_amount, 2) }} ETB</span>
                        </div>
                    @endif

                    <div class="d-flex justify-content-between text-muted small mb-3">
                        <span>VAT (15%):</span>
                        <span class="font-mono fw-semibold text-dark">{{ number_format($order->tax_amount, 2) }} ETB</span>
                    </div>

                    <div class="d-flex justify-content-between fw-bold fs-5 pt-3 border-top">
                        <span>Total:</span>
                        <span class="text-primary font-mono">{{ number_format($order->total_amount, 2) }} ETB</span>
                    </div>
                </div>
            </div>

            <!-- Status History Timeline -->
            <div class="card">
                <div class="card-header bg-white py-2 px-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Status Timeline</h6>
                </div>
                <div class="card-body p-3">
                    <ul class="list-unstyled mb-0">
                        @foreach($order->statusHistory as $hist)
                            <li class="mb-3 position-relative ps-4">
                                <span class="position-absolute start-0 top-0 translate-middle-y mt-2 bg-primary rounded-circle" style="width: 10px; height: 10px;"></span>
                                <div class="fw-semibold small text-dark">{{ strtoupper(str_replace('_', ' ', $hist->status_to)) }}</div>
                                <div class="text-muted" style="font-size: 0.72rem;">
                                    By {{ $hist->user?->name }} &bull; {{ $hist->created_at->format('M d, H:i') }}
                                </div>
                                @if($hist->comments)
                                    <div class="small text-secondary">{{ $hist->comments }}</div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Order Modal -->
@if($order->isOpen())
<div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form method="POST" action="{{ route('orders.cancel', $order) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Authorize Order Cancellation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">
                        Cancelling will revoke kitchen & bar preparation tickets, release the assigned table back to available status, and log the authorizing staff in audit records.
                    </p>
                    <div class="mb-3">
                        <label for="cancellation_reason" class="form-label small fw-semibold">Cancellation Reason <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-3" id="cancellation_reason" name="reason" required placeholder="e.g. Guest changed mind, incorrect entry">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4">Confirm Cancellation</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
    function printOrderTicketDirect(ticketId) {
        const rawHtml = document.getElementById('ticketPrintData_' + ticketId)?.innerHTML;
        if (rawHtml) {
            window.printIrp200dTicket(rawHtml);
        }
    }
</script>
@endpush
@endsection
