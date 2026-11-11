<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'POS Order Terminal') - {{ config('app.name', 'Kerefa ERP') }}</title>

    <!-- Google Fonts: Inter (Crisp Modern UI) & JetBrains Mono (Thermal & Numbers) with display=swap -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons 1.11.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --bs-body-font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            --bs-body-bg: #f8fafc;
            --font-mono: 'JetBrains Mono', monospace;
        }

        * {
            box-sizing: border-box;
            user-select: none;
            -webkit-user-select: none;
            touch-action: manipulation;
        }

        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
            color: #1e293b;
            font-family: var(--bs-body-font-family);
            overflow: hidden;
            -webkit-font-smoothing: antialiased;
        }

        .font-mono {
            font-family: var(--font-mono) !important;
        }

        .cursor-pointer {
            cursor: pointer;
        }

        .hover-lift {
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }
        .hover-lift:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.06) !important;
        }
        .hover-lift:active {
            transform: scale(0.98);
        }

        /* Custom refined scrollbars */
        ::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
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
            .no-print, header, nav, button, #pos-root {
                display: none !important;
            }
        }
    </style>

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/pos.jsx'])
    @stack('styles')
</head>
<body class="h-100 bg-light">

    @yield('content')

    <!-- Hidden Thermal Print Frame for IRP 200D -->
    <iframe id="irp200dPrintFrame" style="position: absolute; width: 0; height: 0; border: none; visibility: hidden;"></iframe>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // UNIVERSAL PRINT UTILITY FOR THERMAL PRINTER MODEL: IRP 200D (80mm)
        window.printIrp200dTicket = function (ticketContentHtml) {
            const printFrame = document.getElementById('irp200dPrintFrame');
            if (!printFrame) return;
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
                            font-size: 16px;
                            font-weight: 800;
                            margin: 0;
                        }
                        .irp200d-banner {
                            font-size: 14px;
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
                            font-size: 15px;
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
