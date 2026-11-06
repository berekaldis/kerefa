<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - {{ config('app.name', 'Kerefa ERP') }}</title>

    <!-- Google Fonts: Plus Jakarta Sans (Primary UI) & JetBrains Mono (Thermal & Numbers) -->
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
            --kerefa-sidebar-bg: #0b1120;
            --kerefa-sidebar-hover: rgba(255, 255, 255, 0.05);
            --kerefa-header-bg: rgba(255, 255, 255, 0.92);
            --kerefa-body-bg: #f8fafc;
            --kerefa-accent: #d97706;
            --kerefa-accent-hover: #b45309;
            --kerefa-accent-light: #fef3c7;
            --kerefa-primary: #2563eb;
            --kerefa-card-border: #e2e8f0;
            --kerefa-card-hover-border: #cbd5e1;
            --sidebar-width: 270px;
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
            -moz-osx-font-smoothing: grayscale;
        }

        /* Modern subtle custom scrollbars */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Sidebar Styling */
        #sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(180deg, #0b1120 0%, #0f172a 100%);
            color: #94a3b8;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1050;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            overflow-y: auto;
            border-right: 1px solid rgba(255, 255, 255, 0.06);
            display: flex;
            flex-direction: column;
        }

        #sidebar .brand {
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(15, 23, 42, 0.7);
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        #sidebar .brand-icon {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, #d97706, #b45309);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.35rem;
            box-shadow: 0 4px 14px rgba(217, 119, 6, 0.35);
        }

        #sidebar .brand-text {
            font-size: 1.15rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: 0.5px;
            line-height: 1.2;
        }

        #sidebar .sidebar-heading {
            font-size: 0.68rem;
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: 1.2px;
            color: #64748b;
            padding: 1.25rem 1.5rem 0.4rem;
        }

        #sidebar .nav-link {
            color: #94a3b8;
            padding: 0.65rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.85rem;
            font-size: 0.88rem;
            font-weight: 500;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            border-left: 3px solid transparent;
            margin: 0.1rem 0;
        }

        #sidebar .nav-link:hover {
            color: #f8fafc;
            background-color: var(--kerefa-sidebar-hover);
            transform: translateX(3px);
        }

        #sidebar .nav-link.active {
            color: #ffffff;
            background: linear-gradient(90deg, rgba(217, 119, 6, 0.15) 0%, rgba(217, 119, 6, 0.02) 100%);
            border-left-color: var(--kerefa-accent);
            font-weight: 600;
        }

        #sidebar .nav-link i {
            font-size: 1.15rem;
            width: 22px;
            text-align: center;
        }

        /* Sidebar Backdrop Overlay on Mobile */
        #sidebarBackdrop {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(4px);
            z-index: 1045;
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        #sidebarBackdrop.show {
            display: block;
            opacity: 1;
        }

        /* Main Content Wrapper */
        #content-wrapper {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            width: calc(100% - var(--sidebar-width));
        }

        /* Header Navbar */
        .top-navbar {
            background-color: var(--kerefa-header-bg);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--kerefa-card-border);
            padding: 0.75rem 1.5rem;
            position: sticky;
            top: 0;
            z-index: 1020;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .btn-kerefa {
            background: linear-gradient(135deg, #d97706, #b45309);
            color: #ffffff;
            font-weight: 600;
            border: none;
            box-shadow: 0 2px 8px rgba(217, 119, 6, 0.25);
            transition: all 0.2s ease;
        }
        .btn-kerefa:hover {
            background: linear-gradient(135deg, #b45309, #92400e);
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(217, 119, 6, 0.35);
        }
        .btn-kerefa:active {
            transform: scale(0.98);
        }

        /* Card and Interactive Elevations */
        .card {
            border: 1px solid var(--kerefa-card-border);
            border-radius: 14px;
            box-shadow: 0 2px 6px -1px rgba(0, 0, 0, 0.04), 0 2px 4px -2px rgba(0, 0, 0, 0.02);
            transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.2s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.2s ease;
            background: #ffffff;
        }

        .card-hover:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px -4px rgba(15, 23, 42, 0.08), 0 4px 8px -2px rgba(15, 23, 42, 0.02);
            border-color: var(--kerefa-card-hover-border);
        }

        .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid var(--kerefa-card-border);
            padding: 1rem 1.25rem;
            font-weight: 600;
            border-top-left-radius: 14px !important;
            border-top-right-radius: 14px !important;
        }

        /* Monospace / Code Elements */
        .font-mono, code, .order-code {
            font-family: var(--font-mono) !important;
        }

        /* Pulse Animation for Active Tickets / Statuses */
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

        /* Responsive Breakpoints */
        @media (max-width: 991.98px) {
            #sidebar {
                transform: translateX(-100%);
            }
            #sidebar.show {
                transform: translateX(0);
            }
            #content-wrapper {
                margin-left: 0;
                width: 100%;
            }
            .top-navbar {
                padding: 0.75rem 1rem;
            }
        }

        /* ==========================================================================
           PRINT ENGINE OPTIMIZED FOR 80mm THERMAL ORDER PRINTER (MODEL: IRP 200D)
           Paper Width: 80mm | Printable Zone: 72mm (576 dots @ 203 DPI)
           ========================================================================== */
        @media print {
            @page {
                size: 80mm auto;
                margin: 0mm;
            }
            html, body {
                width: 80mm !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
                color: #000000 !important;
                font-family: 'JetBrains Mono', 'Courier New', Courier, monospace !important;
                font-size: 12px !important;
                line-height: 1.25 !important;
            }
            /* Hide entire standard application UI during print */
            #sidebar,
            #sidebarBackdrop,
            .top-navbar,
            footer,
            .no-print,
            .btn,
            .alert,
            .modal-header,
            .modal-footer,
            .pos-container,
            #content-wrapper > nav,
            #content-wrapper > footer {
                display: none !important;
            }

            #content-wrapper {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }

            main {
                padding: 0 !important;
            }

            /* Dedicated 80mm Thermal Receipt Structure */
            .irp200d-print-zone {
                display: block !important;
                width: 72mm !important;
                margin: 0 auto !important;
                padding: 3mm 0 10mm 0 !important;
                color: #000000 !important;
                background: #ffffff !important;
            }

            .irp200d-ticket-header {
                text-align: center;
                border-bottom: 2px dashed #000;
                padding-bottom: 3mm;
                margin-bottom: 3mm;
            }

            .irp200d-title {
                font-size: 15px;
                font-weight: 800;
                letter-spacing: 1px;
                text-transform: uppercase;
                margin: 0;
            }

            .irp200d-banner {
                font-size: 14px;
                font-weight: 900;
                background: #000;
                color: #fff !important;
                padding: 3px 0;
                margin: 4px 0;
                text-align: center;
                letter-spacing: 1px;
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
                margin: 3mm 0;
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
                padding: 4px 0;
                vertical-align: top;
            }

            .irp200d-item-qty {
                font-size: 14px;
                font-weight: 800;
                width: 25px;
            }

            .irp200d-item-name {
                font-size: 13px;
                font-weight: 700;
            }

            .irp200d-item-notes {
                font-size: 11px;
                font-style: italic;
                padding-left: 10px;
                font-weight: bold;
            }

            .irp200d-cutter-feed {
                height: 15mm;
                border-bottom: 1px dotted #888;
                margin-top: 5mm;
                text-align: center;
                font-size: 9px;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
    <!-- Mobile Sidebar Backdrop Overlay -->
    <div id="sidebarBackdrop"></div>

    <div class="d-flex">
        <!-- Sidebar Navigation -->
        <aside id="sidebar">
            <div class="brand">
                <div class="d-flex align-items-center gap-3">
                    <div class="brand-icon">
                        <i class="bi bi-cup-hot-fill"></i>
                    </div>
                    <div>
                        <div class="brand-text">KEREFA ERP</div>
                        <div class="text-secondary small fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.3px;">Restaurant System</div>
                    </div>
                </div>
                <!-- Close Button on Mobile Drawer -->
                <button type="button" class="btn btn-sm btn-link text-white-50 p-1 d-lg-none" id="sidebarCloseBtn" aria-label="Close Sidebar">
                    <i class="bi bi-x-lg fs-5"></i>
                </button>
            </div>

            <div class="py-2 flex-grow-1">
                <div class="sidebar-heading">Operational</div>
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
                </a>

                @if(auth()->user()->hasPermission('pos.view'))
                <div class="sidebar-heading">Restaurant & POS</div>
                <a href="{{ route('pos.terminal') }}" class="nav-link {{ request()->routeIs('pos.terminal') ? 'active' : '' }}">
                    <i class="bi bi-grid-3x3-gap"></i> <span>POS Terminal</span>
                </a>
                <a href="{{ route('tables.index') }}" class="nav-link {{ request()->routeIs('tables.*') ? 'active' : '' }}">
                    <i class="bi bi-grid-3x3"></i> <span>Table Floor Plan</span>
                </a>
                <a href="{{ route('orders.index') }}" class="nav-link {{ request()->routeIs('orders.*') ? 'active' : '' }}">
                    <i class="bi bi-receipt"></i> <span>Orders Tracking</span>
                </a>
                @if(auth()->user()->hasPermission('pos.menu.manage'))
                <a href="{{ route('menu.index') }}" class="nav-link {{ request()->routeIs('menu.*') ? 'active' : '' }}">
                    <i class="bi bi-card-list"></i> <span>Menu Products</span>
                </a>
                @endif
                <a href="{{ route('kds.index') }}" class="nav-link {{ request()->routeIs('kds.*') ? 'active' : '' }}">
                    <i class="bi bi-tv"></i> <span>Kitchen (KDS)</span>
                </a>
                <a href="{{ route('bds.index') }}" class="nav-link {{ request()->routeIs('bds.*') ? 'active' : '' }}">
                    <i class="bi bi-cup-straw"></i> <span>Bar (BDS)</span>
                </a>
                @endif

                @if(auth()->user()->hasPermission('inventory.view'))
                <div class="sidebar-heading">Supply Chain</div>
                <a href="#" class="nav-link text-muted" title="Phase 4 (In Development)">
                    <i class="bi bi-box-seam"></i> <span>Stock & Inventory <span class="badge bg-secondary ms-1" style="font-size: 0.65rem;">Phase 4</span></span>
                </a>
                <a href="#" class="nav-link text-muted" title="Phase 4 (In Development)">
                    <i class="bi bi-truck"></i> <span>Procurement</span>
                </a>
                @endif

                @if(auth()->user()->hasPermission('fb.recipes.view'))
                <div class="sidebar-heading">Food & Beverage</div>
                <a href="#" class="nav-link text-muted" title="Phase 5 (In Development)">
                    <i class="bi bi-book"></i> <span>Recipes & Costing <span class="badge bg-secondary ms-1" style="font-size: 0.65rem;">Phase 5</span></span>
                </a>
                @endif

                @if(auth()->user()->hasPermission('finance.view'))
                <div class="sidebar-heading">Finance & Accounts</div>
                <a href="#" class="nav-link text-muted" title="Phase 6 (In Development)">
                    <i class="bi bi-journal-text"></i> <span>General Ledger <span class="badge bg-secondary ms-1" style="font-size: 0.65rem;">Phase 6</span></span>
                </a>
                @endif

                @if(auth()->user()->hasPermission('hr.employees.view'))
                <div class="sidebar-heading">Human Resources</div>
                <a href="#" class="nav-link text-muted" title="Phase 7 (In Development)">
                    <i class="bi bi-people"></i> <span>Staff Directory <span class="badge bg-secondary ms-1" style="font-size: 0.65rem;">Phase 7</span></span>
                </a>
                @endif

                @if(auth()->user()->hasPermission('users.view') || auth()->user()->hasPermission('branches.view') || auth()->user()->hasPermission('settings.view'))
                <div class="sidebar-heading">Administration</div>

                @if(auth()->user()->hasPermission('users.view'))
                <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                    <i class="bi bi-person-badge"></i> <span>Users & Access</span>
                </a>
                @endif

                @if(auth()->user()->hasPermission('roles.view'))
                <a href="{{ route('roles.index') }}" class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                    <i class="bi bi-shield-check"></i> <span>Roles & Permissions</span>
                </a>
                @endif

                @if(auth()->user()->hasPermission('branches.view'))
                <a href="{{ route('branches.index') }}" class="nav-link {{ request()->routeIs('branches.*') ? 'active' : '' }}">
                    <i class="bi bi-building"></i> <span>Branches</span>
                </a>
                @endif

                @if(auth()->user()->hasPermission('audit.view'))
                <a href="{{ route('audit-logs.index') }}" class="nav-link {{ request()->routeIs('audit-logs.*') ? 'active' : '' }}">
                    <i class="bi bi-clock-history"></i> <span>Audit Trail</span>
                </a>
                @endif

                @if(auth()->user()->hasPermission('settings.view'))
                <a href="{{ route('settings.index') }}" class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                    <i class="bi bi-gear"></i> <span>System Settings</span>
                </a>
                @endif
                @endif
            </div>

            <!-- Printer Support Badge Footer -->
            <div class="p-3 border-top border-secondary border-opacity-25 small text-secondary">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-printer-fill text-warning"></i>
                    <div>
                        <div class="text-white-50 fw-semibold" style="font-size: 0.72rem;">Printer Configured</div>
                        <div class="font-mono text-muted" style="font-size: 0.68rem;">Model: IRP 200D (80mm)</div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div id="content-wrapper">
            <!-- Top Header Navbar -->
            <nav class="top-navbar d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2 gap-md-3">
                    <button class="btn btn-sm btn-light border d-lg-none px-2 py-1" id="sidebarToggle" aria-label="Toggle Sidebar">
                        <i class="bi bi-list fs-5"></i>
                    </button>

                    <!-- Active Branch Indicator / Switcher -->
                    @if(isset($currentBranch))
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light border dropdown-toggle d-flex align-items-center gap-2 shadow-sm rounded-pill px-3" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="pulse-dot"></span>
                            <span class="fw-semibold text-truncate" style="max-width: 140px;">{{ $currentBranch->name }}</span>
                            <span class="badge bg-secondary font-mono d-none d-sm-inline" style="font-size: 0.65rem;">{{ $currentBranch->code }}</span>
                        </button>
                        @if(isset($accessibleBranches) && $accessibleBranches->count() > 1)
                        <ul class="dropdown-menu shadow-lg border-0 rounded-3 mt-1">
                            <li class="dropdown-header small text-uppercase fw-bold text-muted">Switch Active Branch</li>
                            @foreach($accessibleBranches as $branch)
                            <li>
                                <form method="POST" action="{{ route('branches.switch') }}">
                                    @csrf
                                    <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                                    <button type="submit" class="dropdown-item d-flex align-items-center justify-content-between py-2 {{ $currentBranch->id === $branch->id ? 'active' : '' }}">
                                        <span>{{ $branch->name }}</span>
                                        <span class="badge {{ $currentBranch->id === $branch->id ? 'bg-light text-dark' : 'bg-secondary' }} ms-2 font-mono">{{ $branch->code }}</span>
                                    </button>
                                </form>
                            </li>
                            @endforeach
                        </ul>
                        @endif
                    </div>
                    @endif
                </div>

                <!-- Right Nav Items -->
                <div class="d-flex align-items-center gap-2 gap-md-3">
                    <!-- Quick Links for Fast Navigation -->
                    @if(auth()->user()->hasPermission('pos.view'))
                    <a href="{{ route('pos.terminal') }}" class="btn btn-sm btn-outline-dark d-none d-md-inline-flex align-items-center gap-1 rounded-pill px-3">
                        <i class="bi bi-grid-3x3-gap-fill text-warning"></i> <span>POS</span>
                    </a>
                    <a href="{{ route('kds.index') }}" class="btn btn-sm btn-outline-danger d-none d-md-inline-flex align-items-center gap-1 rounded-pill px-3">
                        <i class="bi bi-fire"></i> <span>KDS</span>
                    </a>
                    <a href="{{ route('bds.index') }}" class="btn btn-sm btn-outline-info d-none d-md-inline-flex align-items-center gap-1 rounded-pill px-3">
                        <i class="bi bi-cup-straw"></i> <span>BDS</span>
                    </a>
                    @endif

                    <!-- Current User Menu -->
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center gap-2 text-decoration-none dropdown-toggle p-1 rounded-pill border bg-white shadow-sm" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="rounded-circle bg-dark text-white d-flex align-items-center justify-content-center fw-bold" style="width: 34px; height: 34px; font-size: 0.85rem;">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                            <div class="d-none d-sm-block text-start pe-2">
                                <div class="fw-bold text-dark small" style="line-height: 1.1;">{{ auth()->user()->name }}</div>
                                <div class="text-muted" style="font-size: 0.7rem;">
                                    {{ auth()->user()->roles->first()?->name ?? 'Staff' }}
                                </div>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 mt-2">
                            <li><h6 class="dropdown-header text-muted">{{ auth()->user()->email }}</h6></li>
                            <li><a class="dropdown-item py-2" href="{{ route('profile') }}"><i class="bi bi-person me-2 text-primary"></i> My Profile & Password</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item py-2 text-danger">
                                        <i class="bi bi-box-arrow-right me-2"></i> Sign Out
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <!-- Page Content Body -->
            <main class="p-2 p-sm-3 p-md-4 flex-grow-1">
                <!-- Flash Messages with Animated Dismiss -->
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 rounded-3 mb-3 d-flex align-items-center" role="alert">
                        <i class="bi bi-check-circle-fill text-success fs-5 me-2"></i>
                        <div class="flex-grow-1 fw-medium">{{ session('success') }}</div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 rounded-3 mb-3 d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-triangle-fill text-danger fs-5 me-2"></i>
                        <div class="flex-grow-1 fw-medium">{{ session('error') }}</div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if (session('info'))
                    <div class="alert alert-info alert-dismissible fade show shadow-sm border-0 rounded-3 mb-3 d-flex align-items-center" role="alert">
                        <i class="bi bi-info-circle-fill text-info fs-5 me-2"></i>
                        <div class="flex-grow-1 fw-medium">{{ session('info') }}</div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @yield('content')
            </main>

            <!-- Footer -->
            <footer class="mt-auto py-3 px-4 bg-white border-top text-muted small d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                <div>
                    &copy; {{ date('Y') }} <strong>Kerefa Restaurant</strong>. All rights reserved.
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-secondary border font-mono">ERP v1.0.0</span>
                    <span class="badge bg-light text-muted border font-mono"><i class="bi bi-printer me-1"></i>IRP 200D Ready</span>
                </div>
            </footer>
        </div>
    </div>

    <!-- Hidden Thermal Print Iframe Container for Instant ESC/POS IRP 200D Printing without opening page tabs -->
    <iframe id="irp200dPrintFrame" style="position: absolute; width: 0; height: 0; border: none; visibility: hidden;"></iframe>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Responsive Mobile Sidebar Drawer Toggle & Overlay
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');
        const sidebarBackdrop = document.getElementById('sidebarBackdrop');

        function openSidebar() {
            sidebar.classList.add('show');
            sidebarBackdrop.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            sidebar.classList.remove('show');
            sidebarBackdrop.classList.remove('show');
            document.body.style.overflow = '';
        }

        sidebarToggle?.addEventListener('click', openSidebar);
        sidebarCloseBtn?.addEventListener('click', closeSidebar);
        sidebarBackdrop?.addEventListener('click', closeSidebar);

        // Close sidebar when clicking any navigation link on mobile
        document.querySelectorAll('#sidebar .nav-link').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 992) {
                    closeSidebar();
                }
            });
        });

        // =========================================================================
        // UNIVERSAL PRINT UTILITY FOR THERMAL PRINTER MODEL: IRP 200D (80mm)
        // =========================================================================
        window.printIrp200dTicket = function (ticketContentHtml) {
            const printFrame = document.getElementById('irp200dPrintFrame');
            const doc = printFrame.contentWindow.document;

            doc.open();
            doc.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <title>IRP 200D Thermal Print</title>
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
                            letter-spacing: 0.5px;
                            margin: 0;
                        }
                        .irp200d-banner {
                            font-size: 13px;
                            font-weight: 900;
                            border: 2px solid #000;
                            padding: 3px 2px;
                            margin: 4px 0;
                            text-align: center;
                            letter-spacing: 0.5px;
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

            // Wait a tiny moment for fonts and render, then invoke browser print
            setTimeout(() => {
                printFrame.contentWindow.focus();
                printFrame.contentWindow.print();
            }, 300);
        };
    </script>
    @stack('scripts')
</body>
</html>
