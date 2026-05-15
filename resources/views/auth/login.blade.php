<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar — VICZ Implementos</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary: #051436;
            --primary-container: #1c294b;
            --on-primary: #ffffff;
            --secondary: #006d41;
            --background: #f8f9fa;
            --surface: #ffffff;
            --on-surface: #191c1d;
            --on-surface-variant: #45464e;
            --outline: #76777f;
            --outline-variant: #c6c6cf;
            --error: #ba1a1a;
            --error-container: #ffdad6;
            --status-info: #0D6EFD;
            --radius-sm: 0.25rem;
            --radius-md: 0.5rem;
            --shadow: 0px 2px 4px rgba(28,41,75,0.08);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background);
            color: var(--on-surface);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .login-wrapper {
            width: 100%;
            max-width: 420px;
        }

        .brand {
            text-align: center;
            margin-bottom: 32px;
        }

        .brand-wordmark {
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .brand-icon {
            width: 40px;
            height: 40px;
            background-color: var(--primary-container);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .brand-icon svg {
            width: 22px;
            height: 22px;
            fill: #8490b9;
        }

        .brand-name {
            font-family: 'IBM Plex Sans', sans-serif;
            font-size: 22px;
            font-weight: 700;
            color: var(--primary);
            letter-spacing: -0.02em;
        }

        .brand-subtitle {
            font-size: 12px;
            color: var(--on-surface-variant);
            margin-top: 6px;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            font-weight: 600;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--outline-variant);
            border-radius: var(--radius-md);
            padding: 32px;
            box-shadow: var(--shadow);
        }

        .card-title {
            font-family: 'IBM Plex Sans', sans-serif;
            font-size: 20px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 24px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--on-surface-variant);
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            color: var(--on-surface);
            background: var(--surface);
            border: 1px solid var(--outline-variant);
            border-radius: var(--radius-sm);
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .form-control:focus {
            border-color: var(--status-info);
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.20);
        }

        .form-control.is-invalid {
            border-color: var(--error);
        }

        .invalid-feedback {
            font-size: 12px;
            color: var(--error);
            margin-top: 4px;
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 24px;
        }

        .form-check input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--primary-container);
            cursor: pointer;
        }

        .form-check label {
            font-size: 14px;
            color: var(--on-surface-variant);
            cursor: pointer;
        }

        .btn-primary {
            width: 100%;
            padding: 11px 16px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            font-weight: 600;
            color: var(--on-primary);
            background-color: var(--primary);
            border: none;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: background-color 0.15s;
            letter-spacing: 0.01em;
        }

        .btn-primary:hover {
            background-color: var(--primary-container);
        }

        .btn-primary:active {
            background-color: #0a1e52;
        }

        .alert-error {
            background-color: var(--error-container);
            border: 1px solid #f1aaa8;
            border-radius: var(--radius-sm);
            padding: 10px 14px;
            font-size: 13px;
            color: #93000a;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="brand">
            <div class="brand-wordmark">
                <div class="brand-icon">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2L2 7v10l10 5 10-5V7L12 2zm0 2.18L20 8.5v7L12 19.82 4 15.5v-7L12 4.18z"/>
                    </svg>
                </div>
                <span class="brand-name">VICZ Implementos</span>
            </div>
            <p class="brand-subtitle">Sistema de Gestão Interna</p>
        </div>

        <div class="card">
            <h1 class="card-title">Acesso ao sistema</h1>

            @if ($errors->any())
                <div class="alert-error">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.submit') }}">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="email">E-mail</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control @error('email') is-invalid @enderror"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        autofocus
                        required
                    >
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Senha</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control @error('password') is-invalid @enderror"
                        autocomplete="current-password"
                        required
                    >
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-check">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Manter conectado</label>
                </div>

                <button type="submit" class="btn-primary">Entrar</button>
            </form>
        </div>
    </div>
</body>
</html>
