<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Kerefa ERP') }} - Authentication</title>

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
            --kerefa-navy: #0b1120;
            --kerefa-accent: #d97706;
            --kerefa-accent-hover: #b45309;
            --kerefa-bg: #0b1120;
            --font-main: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
        }

        * {
            box-sizing: border-box;
        }

        body {
            background-color: var(--kerefa-bg);
            background-image: 
                radial-gradient(at 0% 0%, rgba(217, 119, 6, 0.12) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(37, 99, 235, 0.1) 0px, transparent 50%);
            font-family: var(--font-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f1f5f9;
            padding: 1rem;
            -webkit-font-smoothing: antialiased;
        }

        .auth-card {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 18px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
            max-width: 440px;
            width: 100%;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .auth-header {
            text-align: center;
            padding: 2.25rem 2rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            background: rgba(11, 17, 32, 0.5);
        }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--kerefa-accent), #92400e);
            color: #fff;
            border-radius: 16px;
            font-size: 1.85rem;
            margin-bottom: 1rem;
            box-shadow: 0 8px 20px rgba(217, 119, 6, 0.35);
        }

        .form-control {
            background-color: rgba(11, 17, 32, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #f8fafc;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            font-size: 0.92rem;
            transition: all 0.2s;
        }

        .form-control:focus {
            background-color: rgba(15, 23, 42, 0.95);
            border-color: var(--kerefa-accent);
            color: #fff;
            box-shadow: 0 0 0 3px rgba(217, 119, 6, 0.25);
        }

        .btn-kerefa {
            background: linear-gradient(135deg, var(--kerefa-accent), #b45309);
            color: #ffffff;
            font-weight: 700;
            padding: 0.8rem 1rem;
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 14px rgba(217, 119, 6, 0.3);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            letter-spacing: 0.3px;
        }

        .btn-kerefa:hover {
            background: linear-gradient(135deg, #b45309, #92400e);
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(217, 119, 6, 0.4);
        }

        .btn-kerefa:active {
            transform: scale(0.98);
        }

        .form-check-input:checked {
            background-color: var(--kerefa-accent);
            border-color: var(--kerefa-accent);
        }

        .font-mono {
            font-family: var(--font-mono);
        }
    </style>
</head>
<body>
    @yield('content')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
