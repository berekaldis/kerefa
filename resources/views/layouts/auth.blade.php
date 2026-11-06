<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Kerefa ERP') }} - Authentication</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --kerefa-primary: #1a2232;
            --kerefa-accent: #c59b27;
            --kerefa-accent-hover: #b0871d;
            --kerefa-bg: #0f1520;
        }
        body {
            background-color: var(--kerefa-bg);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #e2e8f0;
        }
        .auth-card {
            background-color: #17202f;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            max-width: 440px;
            width: 100%;
        }
        .auth-header {
            text-align: center;
            padding: 2.25rem 2rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }
        .brand-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 58px;
            height: 58px;
            background: linear-gradient(135deg, var(--kerefa-accent), #946f14);
            color: #fff;
            border-radius: 14px;
            font-size: 1.75rem;
            margin-bottom: 1rem;
            box-shadow: 0 6px 16px rgba(197, 155, 39, 0.3);
        }
        .form-control {
            background-color: #0f1520;
            border: 1px solid #2d3748;
            color: #f7fafc;
            padding: 0.7rem 1rem;
        }
        .form-control:focus {
            background-color: #131b29;
            border-color: var(--kerefa-accent);
            color: #fff;
            box-shadow: 0 0 0 0.25rem rgba(197, 155, 39, 0.2);
        }
        .btn-kerefa {
            background-color: var(--kerefa-accent);
            color: #0f1520;
            font-weight: 600;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: none;
            transition: all 0.2s ease-in-out;
        }
        .btn-kerefa:hover {
            background-color: var(--kerefa-accent-hover);
            color: #000;
        }
        .form-check-input:checked {
            background-color: var(--kerefa-accent);
            border-color: var(--kerefa-accent);
        }
    </style>
</head>
<body>
    @yield('content')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
