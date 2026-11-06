<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'POS Terminal') - {{ config('app.name', 'Kerefa ERP') }}</title>

    <!-- Google Fonts: Plus Jakarta Sans & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --kerefa-navy: #0f172a;
            --kerefa-accent: #d97706;
            --kerefa-accent-hover: #b45309;
            --kerefa-body-bg: #f1f5f9;
            --font-main: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
        }

        * {
            box-sizing: border-box;
        }

        body {
            background-color: var(--kerefa-body-bg);
            font-family: var(--font-main);
            color: #1e293b;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        .font-mono {
            font-family: var(--font-mono) !important;
        }

        /* Dedicated POS Top Header */
        .pos-topbar {
            background: #0f172a;
            color: #ffffff;
            padding: 0.65rem 1.25rem;
            position: sticky;
            top: 0;
            z-index: 1040;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);
        }

        .pos-brand-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #d97706, #b45309);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.2rem;
            box-shadow: 0 2px 8px rgba(217, 119, 6, 0.35);
        }

        .btn-kerefa {
            background: linear-gradient(135deg, #d97706, #b45309);
            color: #ffffff;
            font-weight: 700;
            border: none;
            box-shadow: 0 2px 8px rgba(217, 119, 6, 0.25);
            transition: all 0.2s ease;
        }
        .btn-kerefa:hover {
            background: linear-gradient(135deg, #b45309, #92400e);
            color: #ffffff;
            transform: translateY(-1px);
        }
        .btn-kerefa:active {
            transform: scale(0.98);
        }

        .pulse-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #10b981;
            position: relative;
        }
        .pulse-dot::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background-color: inherit;
            animation: pulse-ring 1.8s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse-ring {
            0% { transform: scale(1); opacity: 0.8; }
            100% { transform: scale(2.8); opacity: 0; }
        }

        /* ESC/POS Thermal Print Setup for IRP 200D */
        @media print {
            @page {
                size: 80mm auto;
                margin: 0mm;
            }
            body {
                width: 72mm;
                margin: 0 auto;
                padding: 3mm 0;
                font-family: 'JetBrains Mono', 'Courier New', monospace;
                font-size: 12px;
                color: #000;
                background: #fff;
            }
            .pos-topbar, .btn, .no-print, .pos-layout {
                display: none !important;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
    <!-- Dedicated POS Header -->
    <header class="pos-topbar d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <div class="pos-brand-icon">
                    <i class="bi bi-cup-hot-fill"></i>
                </div>
                <div>
                    <div class="fw-bold fs-6" style="letter-spacing: 0.5px; line-height: 1.1;">KEREFA POS</div>
                    <div class="text-white-50" style="font-size: 0.68rem;">Touchscreen Order Terminal</div>
                </div>
            </div>

            <!-- Active Branch Pill -->
            @if(isset($branch))
            <span class="badge bg-dark border border-secondary border-opacity-50 text-white-50 font-mono rounded-pill px-2 py-1 d-none d-sm-inline">
                <i class="bi bi-geo-alt-fill text-danger me-1"></i> {{ $branch->name }}
            </span>
            @endif

            <!-- Station Printer Status -->
            <span class="badge bg-dark border border-warning border-opacity-25 text-warning font-mono rounded-pill px-3 py-1 small d-none d-md-inline-flex align-items-center gap-1">
                <span class="pulse-dot"></span>
                <span>IRP 200D Thermal Printer Ready</span>
            </span>
        </div>

        <div class="d-flex align-items-center gap-2">
            <!-- Current Staff -->
            <div class="d-flex align-items-center gap-2 bg-dark bg-opacity-75 rounded-pill px-3 py-1 border border-secondary border-opacity-25">
                <div class="rounded-circle bg-warning text-dark fw-bold d-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 0.75rem;">
                    {{ substr(auth()->user()->name, 0, 1) }}
                </div>
                <div class="text-start">
                    <div class="fw-bold small text-white" style="line-height: 1;">{{ auth()->user()->name }}</div>
                    <div class="text-white-50" style="font-size: 0.65rem;">{{ auth()->user()->roles->first()?->name ?? 'Staff' }}</div>
                </div>
            </div>

            <!-- Quick Exit to ERP Management -->
            <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-light rounded-pill px-3 d-flex align-items-center gap-1" title="Exit POS to ERP Management">
                <i class="bi bi-box-arrow-left"></i> <span class="d-none d-sm-inline">Exit to ERP</span>
            </a>
        </div>
    </header>

    <!-- POS Main Viewport -->
    <main class="p-2 p-sm-3">
        @yield('content')
    </main>

    <!-- Hidden Thermal Print Frame for IRP 200D -->
    <iframe id="irp200dPrintFrame" style="position: absolute; width: 0; height: 0; border: none; visibility: hidden;"></iframe>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // UNIVERSAL PRINT UTILITY FOR THERMAL PRINTER MODEL: IRP 200D (80mm)
        window.printIrp200dTicket = function (ticketContentHtml) {
            const printFrame = document.getElementById('irp200dPrintFrame');
            const doc = printFrame.contentWindow.document;

            doc.open();
            doc.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <title>IRP 200D Order Print</title>
                    <link rel="preconnect" href="https://fonts.googleapis.com">
                    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
                    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600;700;800&display=swap" rel="stylesheet">
                    <style>
                        @page {
                            size: 80mm auto;
                            margin: 0mm;
                        }
                        * {
                            box-sizing: border-box;
                        }
                        body {
                            width: 72mm;
                            margin: 0 auto;
                            padding: 3mm 0 8mm 0;
                            font-family: 'JetBrains Mono', 'Courier New', Courier, monospace;
                            font-size: 12px;
                            line-height: 1.25;
                            color: #000;
                            background: #fff;
                        }
                        .irp200d-ticket-header {
                            text-align: center;
                            border-bottom: 2px dashed #000;
                            padding-bottom: 2mm;
                            margin-bottom: 2mm;
                        }
                        .irp200d-title {
                            font-size: 15px;
                            font-weight: 800;
                            margin: 0;
                        }
                        .irp200d-banner {
                            font-size: 13px;
                            font-weight: 900;
                            border: 2px solid #000;
                            padding: 3px 2px;
                            margin: 4px 0;
                            text-align: center;
                        }
                        .irp200d-meta-row {
                            display: flex;
                            justify-content: space-between;
                            font-size: 11px;
                            margin: 2px 0;
                        }
                        .irp200d-items-table {
                            width: 100%;
                            border-collapse: collapse;
                            margin: 2mm 0;
                            border-top: 1px dashed #000;
                            border-bottom: 1px dashed #000;
                        }
                        .irp200d-items-table th {
                            font-size: 11px;
                            text-transform: uppercase;
                            border-bottom: 1px dashed #000;
                            padding: 3px 0;
                            text-align: left;
                        }
                        .irp200d-items-table td {
                            padding: 3px 0;
                            vertical-align: top;
                        }
                        .irp200d-item-qty {
                            font-size: 14px;
                            font-weight: 800;
                            width: 28px;
                        }
                        .irp200d-item-name {
                            font-size: 13px;
                            font-weight: 700;
                        }
                        .irp200d-item-notes {
                            font-size: 11px;
                            font-style: italic;
                            padding-left: 10px;
                            font-weight: 600;
                        }
                        .irp200d-cutter-feed {
                            height: 12mm;
                            border-bottom: 1px dotted #888;
                            margin-top: 4mm;
                            text-align: center;
                            font-size: 9px;
                        }
                    </style>
                </head>
                <body>
                    ${ticketContentHtml}
                </body>
                </html>
            `);
            doc.close();

            setTimeout(() => {
                printFrame.contentWindow.focus();
                printFrame.contentWindow.print();
            }, 300);
        };
    </script>
    @stack('scripts')
</body>
</html>
