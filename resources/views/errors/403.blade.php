<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Negado — VICZ Implementos</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary: #051436;
            --primary-container: #1c294b;
            --background: #f8f9fa;
            --surface: #ffffff;
            --on-surface: #191c1d;
            --on-surface-variant: #45464e;
            --outline-variant: #c6c6cf;
            --error: #ba1a1a;
            --error-container: #ffdad6;
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

        .error-card {
            background: var(--surface);
            border: 1px solid var(--outline-variant);
            border-radius: var(--radius-md);
            padding: 48px 40px;
            max-width: 480px;
            width: 100%;
            box-shadow: var(--shadow);
            text-align: center;
        }

        .error-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 56px;
            height: 56px;
            background-color: var(--error-container);
            border-radius: var(--radius-md);
            margin-bottom: 24px;
        }

        .error-badge svg {
            width: 28px;
            height: 28px;
            fill: var(--error);
        }

        .error-code {
            font-family: 'IBM Plex Sans', sans-serif;
            font-size: 48px;
            font-weight: 700;
            color: var(--primary-container);
            letter-spacing: -0.02em;
            line-height: 1;
            margin-bottom: 8px;
        }

        .error-title {
            font-family: 'IBM Plex Sans', sans-serif;
            font-size: 20px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 12px;
        }

        .error-message {
            font-size: 14px;
            color: var(--on-surface-variant);
            line-height: 1.6;
            margin-bottom: 32px;
        }

        .btn-back {
            display: inline-block;
            padding: 10px 20px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            font-weight: 600;
            color: #ffffff;
            background-color: var(--primary);
            border-radius: 0.25rem;
            text-decoration: none;
            transition: background-color 0.15s;
        }

        .btn-back:hover {
            background-color: var(--primary-container);
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-badge">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 1L3 5v6c0 5.25 3.75 10.15 9 11.35C17.25 21.15 21 16.25 21 11V5l-9-4zm0 4l5 2.18V11c0 3.5-2.33 6.79-5 7.93C9.33 17.79 7 14.5 7 11V7.18L12 5zm-1 3v4h2V8h-2zm0 6v2h2v-2h-2z"/>
            </svg>
        </div>
        <div class="error-code">403</div>
        <h1 class="error-title">Acesso negado</h1>
        <p class="error-message">
            Você não tem permissão para acessar esta página.<br>
            Caso acredite que isso seja um erro, contate o administrador do sistema.
        </p>
        @auth
            <a href="{{ route('dashboard') }}" class="btn-back">Voltar ao início</a>
        @else
            <a href="{{ route('login') }}" class="btn-back">Fazer login</a>
        @endauth
    </div>
</body>
</html>
