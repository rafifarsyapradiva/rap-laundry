<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Masuk') — LaundryDesk (RAP Laundry)</title>
    
    <!-- Aset CSS 100% Offline-First (Tanpa CDN / Google Fonts) -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        .auth-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            position: relative;
        }

        .auth-card {
            width: 100%;
            max-width: 440px;
            padding: 2.5rem 2rem;
            position: relative;
            z-index: 10;
        }

        .auth-brand {
            text-align: center;
            margin-bottom: 2rem;
        }

        .auth-brand-icon {
            width: 3.5rem;
            height: 3.5rem;
            margin: 0 auto 0.75rem auto;
            border-radius: var(--radius-lg);
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 16px rgba(37, 99, 235, 0.25);
            color: #FFFFFF;
        }

        .auth-brand-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-main);
            letter-spacing: -0.02em;
        }

        .auth-brand-subtitle {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }

        .password-toggle-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-toggle-btn {
            position: absolute;
            right: 0.75rem;
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 0.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-sm);
        }

        .password-toggle-btn:hover {
            color: var(--primary);
        }

        .remember-wrap {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
            font-size: 0.875rem;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-muted);
            cursor: pointer;
            user-select: none;
        }

        .checkbox-label input[type="checkbox"] {
            width: 1rem;
            height: 1rem;
            accent-color: var(--primary);
            cursor: pointer;
        }

        .btn-submit {
            width: 100%;
            padding: 0.75rem;
            font-size: 0.95rem;
        }

        .auth-footer {
            margin-top: 2rem;
            text-align: center;
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .demo-credentials-box {
            margin-top: 1.5rem;
            padding: 0.875rem 1rem;
            background-color: var(--primary-subtle);
            border: 1px dashed var(--primary-light);
            border-radius: var(--radius-md);
            font-size: 0.775rem;
            color: var(--text-main);
        }

        .demo-credentials-box summary {
            font-weight: 600;
            color: var(--primary-dark);
            cursor: pointer;
        }

        .demo-credentials-list {
            margin-top: 0.5rem;
            display: grid;
            gap: 0.25rem;
            list-style: none;
        }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        @yield('content')
    </div>
</body>
</html>
