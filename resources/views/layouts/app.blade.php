<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - {{ config('app.name', 'Kerefa ERP') }}</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --kerefa-navy: #151d2a;
            --kerefa-sidebar-bg: #111722;
            --kerefa-header-bg: #ffffff;
            --kerefa-body-bg: #f4f6f9;
            --kerefa-accent: #c59b27;
            --kerefa-accent-hover: #b0871d;
            --kerefa-card-border: #e2e8f0;
            --sidebar-width: 260px;
        }

        body {
            background-color: var(--kerefa-body-bg);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #2d3748;
            overflow-x: hidden;
        }

        /* Sidebar Styling */
        #sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background-color: var(--kerefa-sidebar-bg);
            color: #a0aec0;
            transition: all 0.3s ease;
            z-index: 1040;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            overflow-y: auto;
        }

        #sidebar .brand {
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background-color: #0c1017;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        #sidebar .brand-icon {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, var(--kerefa-accent), #8a6813);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.2rem;
        }

        #sidebar .brand-text {
            font-size: 1.1rem;
            font-weight: 700;
            color: #fff;
            letter-spacing: 0.5px;
            line-height: 1.2;
        }

        #sidebar .sidebar-heading {
            font-size: 0.7rem;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 1px;
            color: #64748b;
            padding: 1.25rem 1.5rem 0.5rem;
        }

        #sidebar .nav-link {
            color: #94a3b8;
            padding: 0.65rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.88rem;
            font-weight: 500;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }

        #sidebar .nav-link:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.04);
        }

        #sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(197, 155, 39, 0.12);
            border-left-color: var(--kerefa-accent);
        }

        #sidebar .nav-link i {
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }

        /* Main Content Wrapper */
        #content-wrapper {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
        }

        /* Header Navbar */
        .top-navbar {
            background-color: var(--kerefa-header-bg);
            border-bottom: 1px solid var(--kerefa-card-border);
            padding: 0.75rem 1.75rem;
            position: sticky;
            top: 0;
            z-index: 1020;
        }

        .btn-kerefa {
            background-color: var(--kerefa-accent);
            color: #0f1520;
            font-weight: 600;
            border: none;
        }
        .btn-kerefa:hover {
            background-color: var(--kerefa-accent-hover);
            color: #000;
        }

        .badge-kerefa {
            background-color: rgba(197, 155, 39, 0.15);
            color: #856404;
            border: 1px solid rgba(197, 155, 39, 0.3);
        }

        .card {
            border: 1px solid var(--kerefa-card-border);
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid var(--kerefa-card-border);
            padding: 1rem 1.25rem;
            font-weight: 600;
        }

        @media (max-width: 991.98px) {
            #sidebar {
                margin-left: calc(-1 * var(--sidebar-width));
            }
            #sidebar.show {
                margin-left: 0;
            }
            #content-wrapper {
                margin-left: 0;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar Navigation -->
        <aside id="sidebar">
            <div class="brand">
                <div class="brand-icon">
                    <i class="bi bi-cup-hot-fill"></i>
                </div>
                <div>
                    <div class="brand-text">KEREFA ERP</div>
                    <div class="small text-secondary" style="font-size: 0.7rem;">Restaurant Management</div>
                </div>
            </div>

            <div class="py-2">
                <div class="sidebar-heading">Operational</div>
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
                </a>

                @if(auth()->user()->hasPermission('pos.view'))
                <div class="sidebar-heading">Restaurant & POS</div>
                <a href="#" class="nav-link text-muted" title="Phase 3 (In Development)">
                    <i class="bi bi-grid-3x3-gap"></i> <span>POS Terminal <span class="badge bg-secondary ms-1" style="font-size: 0.65rem;">Phase 3</span></span>
                </a>
                <a href="#" class="nav-link text-muted" title="Phase 3 (In Development)">
                    <i class="bi bi-tv"></i> <span>Kitchen Display (KDS)</span>
                </a>
                <a href="#" class="nav-link text-muted" title="Phase 3 (In Development)">
                    <i class="bi bi-cup-straw"></i> <span>Bar Display (BDS)</span>
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
        </aside>

        <!-- Main Content Area -->
        <div id="content-wrapper" class="flex-grow-1">
            <!-- Top Header Navbar -->
            <nav class="top-navbar d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-sm btn-outline-secondary d-lg-none" id="sidebarToggle">
                        <i class="bi bi-list fs-5"></i>
                    </button>

                    <!-- Active Branch Indicator / Switcher -->
                    @if(isset($currentBranch))
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light border dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-geo-alt-fill text-danger"></i>
                            <span class="fw-semibold">{{ $currentBranch->name }}</span>
                            <span class="badge bg-secondary" style="font-size: 0.65rem;">{{ $currentBranch->code }}</span>
                        </button>
                        @if(isset($accessibleBranches) && $accessibleBranches->count() > 1)
                        <ul class="dropdown-menu shadow-sm">
                            <li class="dropdown-header small text-uppercase">Switch Active Branch</li>
                            @foreach($accessibleBranches as $branch)
                            <li>
                                <form method="POST" action="{{ route('branches.switch') }}">
                                    @csrf
                                    <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                                    <button type="submit" class="dropdown-item d-flex align-items-center justify-content-between {{ $currentBranch->id === $branch->id ? 'active' : '' }}">
                                        <span>{{ $branch->name }}</span>
                                        <span class="badge {{ $currentBranch->id === $branch->id ? 'bg-light text-dark' : 'bg-secondary' }} ms-2">{{ $branch->code }}</span>
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
                <div class="d-flex align-items-center gap-3">
                    <!-- Current User Menu -->
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center gap-2 text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="rounded-circle bg-dark text-white d-flex align-items-center justify-content-center fw-bold" style="width: 36px; height: 36px; font-size: 0.85rem;">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                            <div class="d-none d-sm-block text-start">
                                <div class="fw-semibold text-dark small" style="line-height: 1.1;">{{ auth()->user()->name }}</div>
                                <div class="text-muted" style="font-size: 0.72rem;">
                                    {{ auth()->user()->roles->first()?->name ?? 'Staff' }}
                                </div>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li><h6 class="dropdown-header">{{ auth()->user()->email }}</h6></li>
                            <li><a class="dropdown-item" href="{{ route('profile') }}"><i class="bi bi-person me-2"></i> My Profile & Password</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-box-arrow-right me-2"></i> Sign Out
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <!-- Page Content Body -->
            <main class="p-3 p-md-4">
                <!-- Flash Messages -->
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if (session('info'))
                    <div class="alert alert-info alert-dismissible fade show shadow-sm" role="alert">
                        <i class="bi bi-info-circle-fill me-2"></i> {{ session('info') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @yield('content')
            </main>

            <!-- Footer -->
            <footer class="mt-auto py-3 px-4 bg-white border-top text-muted small d-flex justify-content-between align-items-center">
                <div>
                    &copy; {{ date('Y') }} <strong>Kerefa Restaurant</strong>. All rights reserved.
                </div>
                <div>
                    <span class="badge bg-light text-secondary border">ERP v1.0.0 (Phase 2 Core Active)</span>
                </div>
            </footer>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('sidebarToggle')?.addEventListener('click', function () {
            document.getElementById('sidebar').classList.toggle('show');
        });
    </script>
    @stack('scripts')
</body>
</html>
