@extends('layouts.app')

@section('title', 'Station Mapping & Printer Configuration')

@section('content')
<div class="container-fluid px-1 px-sm-2 px-md-3">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-warning bg-opacity-10 text-warning rounded-circle p-2"><i class="bi bi-printer-fill fs-5"></i></span>
                <div>
                    <h4 class="fw-bold mb-0 text-dark">Station Mapping & Order Printers</h4>
                    <span class="text-muted small">Route food, drinks & coffee to physical thermal printers (Model: IRP 200D)</span>
                </div>
            </div>
        </div>
        @if(auth()->user()->hasPermission('settings.manage'))
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-kerefa rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addStationModal">
                <i class="bi bi-plus-lg me-1"></i> Add Station
            </button>
        </div>
        @endif
    </div>

    <!-- Overview Bar -->
    <div class="row g-2 mb-3">
        <div class="col-6 col-sm-3">
            <div class="card p-2 border-0 shadow-sm rounded-3 d-flex flex-row align-items-center gap-3">
                <div class="rounded-circle bg-dark text-white p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                    <i class="bi bi-diagram-3-fill"></i>
                </div>
                <div>
                    <div class="text-muted small" style="font-size: 0.72rem;">Total Stations</div>
                    <div class="fw-bold font-mono fs-5">{{ count($stations) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-3">
            <div class="card p-2 border-0 shadow-sm rounded-3 d-flex flex-row align-items-center gap-3">
                <div class="rounded-circle bg-success bg-opacity-10 text-success p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div>
                    <div class="text-muted small" style="font-size: 0.72rem;">Active Stations</div>
                    <div class="fw-bold font-mono fs-5 text-success">{{ $stations->where('is_active', true)->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-3">
            <div class="card p-2 border-0 shadow-sm rounded-3 d-flex flex-row align-items-center gap-3">
                <div class="rounded-circle bg-warning bg-opacity-10 text-warning p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                    <i class="bi bi-printer-fill"></i>
                </div>
                <div>
                    <div class="text-muted small" style="font-size: 0.72rem;">Printer Standard</div>
                    <div class="fw-bold font-mono text-dark" style="font-size: 0.95rem;">IRP 200D (80mm)</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-3">
            <div class="card p-2 border-0 shadow-sm rounded-3 d-flex flex-row align-items-center gap-3">
                <div class="rounded-circle bg-info bg-opacity-10 text-info p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                    <i class="bi bi-broadcast"></i>
                </div>
                <div>
                    <div class="text-muted small" style="font-size: 0.72rem;">Auto-Print</div>
                    <div class="fw-bold font-mono fs-5 text-info">Enabled</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stations Grid -->
    <div class="row g-3">
        @forelse($stations as $st)
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 card-hover overflow-hidden">
                    <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            @if($st->station_type === 'bar' || $st->station_type === 'cafe')
                                <span class="badge bg-info bg-opacity-25 text-info rounded-circle p-2"><i class="bi bi-cup-straw fs-6"></i></span>
                            @else
                                <span class="badge bg-danger bg-opacity-25 text-danger rounded-circle p-2"><i class="bi bi-fire fs-6"></i></span>
                            @endif
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">{{ $st->name }}</h6>
                                <span class="badge bg-light text-muted border font-mono" style="font-size: 0.65rem;">{{ $st->code }}</span>
                            </div>
                        </div>
                        <div>
                            @if($st->is_active)
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2 py-1 small">
                                    <span class="pulse-dot me-1"></span> Active
                                </span>
                            @else
                                <span class="badge bg-secondary text-white rounded-pill px-2 py-1 small">Offline</span>
                            @endif
                        </div>
                    </div>

                    <div class="card-body p-3">
                        <div class="p-2 bg-light rounded-3 mb-2 small">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Printer Model:</span>
                                <strong class="text-dark font-mono"><i class="bi bi-printer me-1 text-warning"></i>{{ $st->printer_model }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Paper Width:</span>
                                <span class="font-mono fw-semibold">{{ $st->paper_width_mm }}mm Thermal</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Connection:</span>
                                <span class="badge bg-white text-dark border font-mono" style="font-size: 0.68rem;">{{ strtoupper($st->printer_connection) }}</span>
                            </div>
                            @if($st->printer_ip)
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">IP / Port:</span>
                                <span class="font-mono text-primary">{{ $st->printer_ip }}:{{ $st->printer_port }}</span>
                            </div>
                            @endif
                        </div>

                        <!-- Mapped Categories -->
                        <div class="mb-2">
                            <span class="text-muted small fw-semibold d-block mb-1">Mapped Menu Categories:</span>
                            <div class="d-flex flex-wrap gap-1">
                                @forelse($st->categories as $c)
                                    <span class="badge bg-light text-dark border rounded-pill px-2 py-1 small">{{ $c->name }}</span>
                                @empty
                                    <span class="text-muted small fst-italic">Default for all {{ $st->station_type }} items</span>
                                @endforelse
                            </div>
                        </div>

                        @if($st->notes)
                            <p class="text-muted small mb-0 mt-2 fst-italic" style="font-size: 0.75rem;">{{ $st->notes }}</p>
                        @endif
                    </div>

                    <div class="card-footer bg-white py-2 px-3 border-top d-flex justify-content-between align-items-center">
                        <!-- Test Print Button -->
                        <button type="button" class="btn btn-sm btn-outline-dark rounded-pill px-3 font-mono" onclick="testStationPrint('{{ addslashes($st->name) }}', '{{ $st->code }}', '{{ $st->printer_model }}', '{{ $st->station_type }}')">
                            <i class="bi bi-printer-fill text-warning me-1"></i> Test IRP 200D
                        </button>

                        @if(auth()->user()->hasPermission('settings.manage'))
                        <div class="d-flex gap-1">
                            <form method="POST" action="{{ route('admin.stations.toggle', $st) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm {{ $st->is_active ? 'btn-outline-warning' : 'btn-outline-success' }} rounded-circle p-1" style="width: 28px; height: 28px;" title="{{ $st->is_active ? 'Deactivate' : 'Activate' }}">
                                    <i class="bi {{ $st->is_active ? 'bi-pause-fill' : 'bi-play-fill' }}"></i>
                                </button>
                            </form>
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle p-1" style="width: 28px; height: 28px;" data-bs-toggle="modal" data-bs-target="#editStationModal{{ $st->id }}" title="Edit Station">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form method="POST" action="{{ route('admin.stations.destroy', $st) }}" class="d-inline" onsubmit="return confirm('Delete or deactivate station {{ $st->name }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle p-1" style="width: 28px; height: 28px;" title="Delete Station">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Edit Station Modal -->
            <div class="modal fade" id="editStationModal{{ $st->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg rounded-4">
                        <form method="POST" action="{{ route('admin.stations.update', $st) }}">
                            @csrf
                            @method('PUT')
                            <div class="modal-header">
                                <h6 class="modal-title fw-bold">Edit Station: {{ $st->name }}</h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-2 mb-2">
                                    <div class="col-8">
                                        <label class="form-label small fw-semibold">Station Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control rounded-3" value="{{ $st->name }}" required>
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label small fw-semibold">Station Code <span class="text-danger">*</span></label>
                                        <input type="text" name="code" class="form-control rounded-3 font-mono" value="{{ $st->code }}" required>
                                    </div>
                                </div>

                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold">Station Type <span class="text-danger">*</span></label>
                                        <select name="station_type" class="form-select rounded-3" required>
                                            <option value="kitchen" {{ $st->station_type === 'kitchen' ? 'selected' : '' }}>Kitchen (Food)</option>
                                            <option value="bar" {{ $st->station_type === 'bar' ? 'selected' : '' }}>Bar (Drinks & Coffee)</option>
                                            <option value="grill" {{ $st->station_type === 'grill' ? 'selected' : '' }}>Grill Station</option>
                                            <option value="cafe" {{ $st->station_type === 'cafe' ? 'selected' : '' }}>Cafe / Pastry</option>
                                            <option value="other" {{ $st->station_type === 'other' ? 'selected' : '' }}>Other Station</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold">Printer Model <span class="text-danger">*</span></label>
                                        <input type="text" name="printer_model" class="form-control rounded-3 font-mono" value="{{ $st->printer_model }}" required>
                                    </div>
                                </div>

                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold">Printer Connection</label>
                                        <select name="printer_connection" class="form-select rounded-3">
                                            <option value="thermal_80mm" {{ $st->printer_connection === 'thermal_80mm' ? 'selected' : '' }}>80mm ESC/POS Thermal</option>
                                            <option value="network_ip" {{ $st->printer_connection === 'network_ip' ? 'selected' : '' }}>Network TCP/IP</option>
                                            <option value="usb_spooler" {{ $st->printer_connection === 'usb_spooler' ? 'selected' : '' }}>USB Spooler</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold">Paper Width</label>
                                        <select name="paper_width_mm" class="form-select rounded-3 font-mono">
                                            <option value="80" {{ $st->paper_width_mm == 80 ? 'selected' : '' }}>80mm (Standard)</option>
                                            <option value="58" {{ $st->paper_width_mm == 58 ? 'selected' : '' }}>58mm (Narrow)</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row g-2 mb-2">
                                    <div class="col-8">
                                        <label class="form-label small fw-semibold">IP Address (Network)</label>
                                        <input type="text" name="printer_ip" class="form-control rounded-3 font-mono" value="{{ $st->printer_ip }}" placeholder="192.168.1.200">
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label small fw-semibold">Port</label>
                                        <input type="number" name="printer_port" class="form-control rounded-3 font-mono" value="{{ $st->printer_port }}">
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label small fw-semibold">Mapped Menu Categories</label>
                                    <div class="p-2 border rounded-3 bg-light overflow-y-auto" style="max-height: 120px;">
                                        @foreach($categories as $cat)
                                            <div class="form-check form-check-inline me-3 mb-1">
                                                <input class="form-check-input" type="checkbox" name="category_ids[]" value="{{ $cat->id }}" id="cat_{{ $st->id }}_{{ $cat->id }}"
                                                       {{ $cat->order_station_id == $st->id ? 'checked' : '' }}>
                                                <label class="form-check-label small" for="cat_{{ $st->id }}_{{ $cat->id }}">{{ $cat->name }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold">Print Copies</label>
                                        <input type="number" name="print_copies" class="form-control rounded-3 font-mono" value="{{ $st->print_copies }}" min="1" max="5">
                                    </div>
                                    <div class="col-6 d-flex align-items-center pt-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="auto_print" value="1" id="auto_{{ $st->id }}" {{ $st->auto_print ? 'checked' : '' }}>
                                            <label class="form-check-label small" for="auto_{{ $st->id }}">Auto-Print On Submit</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label small fw-semibold">Notes / Location Instructions</label>
                                    <input type="text" name="notes" class="form-control rounded-3" value="{{ $st->notes }}" placeholder="e.g. Near Hot Line Counter">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary rounded-pill px-3">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 py-5 text-center text-muted">
                <i class="bi bi-printer fs-1 text-secondary opacity-50"></i>
                <h5 class="fw-bold mt-2">No Order Stations Configured</h5>
                <p class="small text-muted mb-3">Add preparation stations to route tickets directly to your IRP 200D thermal printers.</p>
                <button type="button" class="btn btn-sm btn-kerefa rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addStationModal">
                    <i class="bi bi-plus-lg me-1"></i> Add First Station
                </button>
            </div>
        @endforelse
    </div>
</div>

<!-- Add Station Modal -->
<div class="modal fade" id="addStationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form method="POST" action="{{ route('admin.stations.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Register Order Station</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-2">
                        <div class="col-8">
                            <label class="form-label small fw-semibold">Station Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control rounded-3" placeholder="e.g. Main Kitchen, Bar Station" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-semibold">Station Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control rounded-3 font-mono" placeholder="KITCHEN_MAIN" required>
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Station Type <span class="text-danger">*</span></label>
                            <select name="station_type" class="form-select rounded-3" required>
                                <option value="kitchen">Kitchen (Food)</option>
                                <option value="bar">Bar (Drinks & Coffee)</option>
                                <option value="grill">Grill Station</option>
                                <option value="cafe">Cafe / Pastry</option>
                                <option value="other">Other Station</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Printer Model <span class="text-danger">*</span></label>
                            <input type="text" name="printer_model" class="form-control rounded-3 font-mono" value="IRP 200D" required>
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Printer Connection</label>
                            <select name="printer_connection" class="form-select rounded-3">
                                <option value="thermal_80mm">80mm ESC/POS Thermal</option>
                                <option value="network_ip">Network TCP/IP</option>
                                <option value="usb_spooler">USB Spooler</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Paper Width</label>
                            <select name="paper_width_mm" class="form-select rounded-3 font-mono">
                                <option value="80" selected>80mm (Standard)</option>
                                <option value="58">58mm (Narrow)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-8">
                            <label class="form-label small fw-semibold">IP Address (Optional)</label>
                            <input type="text" name="printer_ip" class="form-control rounded-3 font-mono" placeholder="192.168.1.200">
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-semibold">Port</label>
                            <input type="number" name="printer_port" class="form-control rounded-3 font-mono" value="9100">
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Map Menu Categories to this Station</label>
                        <div class="p-2 border rounded-3 bg-light overflow-y-auto" style="max-height: 120px;">
                            @foreach($categories as $cat)
                                <div class="form-check form-check-inline me-3 mb-1">
                                    <input class="form-check-input" type="checkbox" name="category_ids[]" value="{{ $cat->id }}" id="cat_new_{{ $cat->id }}">
                                    <label class="form-check-label small" for="cat_new_{{ $cat->id }}">{{ $cat->name }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Print Copies</label>
                            <input type="number" name="print_copies" class="form-control rounded-3 font-mono" value="1" min="1" max="5">
                        </div>
                        <div class="col-6 d-flex align-items-center pt-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="auto_print" value="1" id="auto_new" checked>
                                <label class="form-check-label small" for="auto_new">Auto-Print on Submit</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Notes / Location Details</label>
                        <input type="text" name="notes" class="form-control rounded-3" placeholder="e.g. Main Kitchen Line Thermal Printer">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-kerefa rounded-pill px-4">Register Station</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function testStationPrint(name, code, model, type) {
        const testTicketHtml = `
            <div class="irp200d-ticket-header">
                <div class="irp200d-title">KEREFA RESTAURANT</div>
                <div style="font-size: 10px; margin-top: 1px;">STATION PRINTER SELF-TEST</div>
                <div class="irp200d-banner">*** ${name.toUpperCase()} ***</div>
            </div>
            <div class="irp200d-meta-row">
                <span><strong>STATION :</strong> ${code}</span>
                <span><strong>TYPE :</strong> ${type.toUpperCase()}</span>
            </div>
            <div class="irp200d-meta-row">
                <span><strong>PRINTER :</strong> ${model} (80mm)</span>
            </div>
            <div class="irp200d-meta-row">
                <span><strong>DATE    :</strong> ${new Date().toLocaleString()}</span>
            </div>
            <table class="irp200d-items-table">
                <thead>
                    <tr>
                        <th style="width: 32px;">TEST</th>
                        <th>COMMUNICATION & PRINT TEST</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="irp200d-item-qty">[ 1 ]</td>
                        <td class="irp200d-item-name">Sample Dish or Drink Item</td>
                    </tr>
                    <tr>
                        <td class="irp200d-item-qty">[ 2 ]</td>
                        <td class="irp200d-item-name">
                            Special Order Instructions
                            <div class="irp200d-item-notes">>> Note: Thermal printer IRP 200D connection verified</div>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div class="irp200d-meta-row" style="font-size: 12px; font-weight: bold;">
                <span>STATUS:</span>
                <span>ONLINE & OPERATIONAL</span>
            </div>
            <div class="irp200d-cutter-feed">
                - - - - - - - - - - - - - [ IRP 200D CUT LINE ] - - - - - - - - - - - - -
            </div>
        `;
        window.printIrp200dTicket(testTicketHtml);
    }
</script>
@endpush
@endsection
