<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'VICZ Implementos')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/app.css">
    @stack('styles')
</head>
<body>

{{-- Mobile: sidebar overlay --}}
<div class="sidebar-overlay" id="sidebar-overlay" onclick="closeSidebar()"></div>

<div class="app-shell">

    {{-- ── Sidebar ──────────────────────────────────────────── --}}
    <aside class="sidebar" id="sidebar" role="navigation" aria-label="Navegação principal">

        {{-- Brand --}}
        <a href="{{ route('dashboard') }}" class="sidebar-brand">
            <div class="sidebar-brand-icon">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2L2 7v10l10 5 10-5V7L12 2zm0 2.18L20 8.5v7L12 19.82 4 15.5v-7L12 4.18z"/>
                </svg>
            </div>
            <div class="sidebar-brand-text">
                <span class="sidebar-brand-name">VICZ Implementos</span>
                <span class="sidebar-brand-sub">Gestão Interna</span>
            </div>
        </a>

        {{-- Navigation --}}
        <nav class="sidebar-nav">

            {{-- Core --}}
            <a href="{{ route('dashboard') }}"
               class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <span class="nav-item-icon">
                    <svg viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
                </span>
                Dashboard
            </a>

            {{-- Finance section --}}
            @canany(['viewAny'], [\App\Models\AccountPayable::class, \App\Models\AccountReceivable::class])
                <div class="sidebar-section-label">Financeiro</div>
            @endcanany

            @can('viewAny', \App\Models\AccountPayable::class)
                <a href="{{ route('payables.index') }}"
                   class="nav-item {{ request()->routeIs('payables.*') ? 'active' : '' }}">
                    <span class="nav-item-icon">
                        <svg viewBox="0 0 24 24"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
                    </span>
                    Contas a Pagar
                </a>
            @endcan

            @can('viewAny', \App\Models\Customer::class)
                <a href="{{ route('customers.index') }}"
                   class="nav-item {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                    <span class="nav-item-icon">
                        <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5C15 14.17 10.33 13 8 13zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.98 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                    </span>
                    Clientes
                </a>
            @endcan

            @can('viewAny', \App\Models\AccountReceivable::class)
                <a href="{{ route('receivables.index') }}"
                   class="nav-item {{ request()->routeIs('receivables.*') ? 'active' : '' }}">
                    <span class="nav-item-icon">
                        <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                    </span>
                    Contas a Receber
                </a>
            @endcan

            @can('report.viewCashFlow')
                <a href="{{ route('reports.cash-flow') }}"
                   class="nav-item {{ request()->routeIs('reports.cash-flow') ? 'active' : '' }}">
                    <span class="nav-item-icon">
                        <svg viewBox="0 0 24 24"><path d="M3.5 18.49l6-6.01 4 4L22 6.92l-1.41-1.41-7.09 7.97-4-4L2 16.99z"/></svg>
                    </span>
                    Previsão de Caixa
                </a>
            @endcan

            {{-- Operations section --}}
            <div class="sidebar-section-label">Operacional</div>

            @can('viewAny', \App\Models\WorkOrder::class)
                <a href="{{ route('work-orders.index') }}"
                   class="nav-item {{ request()->routeIs('work-orders.*') ? 'active' : '' }}">
                    <span class="nav-item-icon">
                        <svg viewBox="0 0 24 24"><path d="M22.7 19l-9.1-9.1a7.006 7.006 0 0 0-8.6-8.6L8.5 4.8 4.8 8.5 1.3 5A7 7 0 0 0 9.9 13.6L19 22.7c.39.39 1.02.39 1.41 0l2.3-2.3c.38-.38.38-1.02-.01-1.4z"/></svg>
                    </span>
                    Ordens de Serviço
                </a>
            @endcan

            {{-- Sales section --}}
            @can('viewAny', \App\Models\Proposal::class)
                <div class="sidebar-section-label">Comercial</div>

                <a href="{{ route('proposals.index') }}"
                   class="nav-item {{ request()->routeIs('proposals.*') ? 'active' : '' }}">
                    <span class="nav-item-icon">
                        <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                    </span>
                    Propostas
                </a>
            @endcan

            {{-- System section --}}
            <div class="sidebar-section-label">Sistema</div>

            @can('viewAny', \App\Models\Alert::class)
                <a href="{{ route('alerts.index') }}"
                   class="nav-item {{ request()->routeIs('alerts.*') ? 'active' : '' }}">
                    <span class="nav-item-icon">
                        <svg viewBox="0 0 24 24"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>
                    </span>
                    Alertas
                    @php
                        $openAlertsCount = \Illuminate\Support\Facades\Cache::remember(
                            'layout_open_alerts_count',
                            now()->addSeconds(30),
                            fn () => \App\Models\Alert::where('status', \App\Enums\AlertStatus::OPEN)->count()
                        );
                    @endphp
                    @if($openAlertsCount > 0)
                        <span class="nav-item-badge">{{ $openAlertsCount > 99 ? '99+' : $openAlertsCount }}</span>
                    @endif
                </a>
            @endcan

            @canany(['report.viewFinancial', 'report.viewOperational', 'report.viewSales'])
                @php
                    $reportRoute = auth()->user()->can('report.viewFinancial')
                        ? 'reports.financial'
                        : (auth()->user()->can('report.viewOperational') ? 'reports.operational' : 'reports.sales');
                @endphp
                <a href="{{ route($reportRoute) }}"
                   class="nav-item {{ request()->routeIs('reports.financial') || request()->routeIs('reports.operational') || request()->routeIs('reports.sales') ? 'active' : '' }}">
                    <span class="nav-item-icon">
                        <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>
                    </span>
                    Relatorios
                </a>
            @endcanany

            @can('viewAny', \App\Models\AuditLog::class)
                <a href="{{ route('audit-logs.index') }}"
                   class="nav-item {{ request()->routeIs('audit-logs.*') ? 'active' : '' }}">
                    <span class="nav-item-icon">
                        <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                    </span>
                    Auditoria
                </a>
            @endcan

            @if(Auth::user()->isAdmin())
                <a href="{{ route('settings.index') }}"
                   class="nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                    <span class="nav-item-icon">
                        <svg viewBox="0 0 24 24"><path d="M19.14,12.94c0.04-0.3,0.06-0.61,0.06-0.94c0-0.32-0.02-0.64-0.07-0.94l2.03-1.58c0.18-0.14,0.23-0.41,0.12-0.61 l-1.92-3.32c-0.12-0.22-0.37-0.29-0.59-0.22l-2.39,0.96c-0.5-0.38-1.03-0.7-1.62-0.94L14.4,2.81c-0.04-0.24-0.24-0.41-0.48-0.41 h-3.84c-0.24,0-0.43,0.17-0.47,0.41L9.25,5.35C8.66,5.59,8.12,5.92,7.63,6.29L5.24,5.33c-0.22-0.08-0.47,0-0.59,0.22L2.74,8.87 C2.62,9.08,2.66,9.34,2.86,9.48l2.03,1.58C4.84,11.36,4.8,11.69,4.8,12s0.02,0.64,0.07,0.94l-2.03,1.58 c-0.18,0.14-0.23,0.41-0.12,0.61l1.92,3.32c0.12,0.22,0.37,0.29,0.59,0.22l2.39-0.96c0.5,0.38,1.03,0.7,1.62,0.94l0.36,2.54 c0.05,0.24,0.24,0.41,0.48,0.41h3.84c0.24,0,0.44-0.17,0.47-0.41l0.36-2.54c0.59-0.24,1.13-0.56,1.62-0.94l2.39,0.96 c0.22,0.08,0.47,0,0.59-0.22l1.92-3.32c0.12-0.22,0.07-0.47-0.12-0.61L19.14,12.94z M12,15.6c-1.98,0-3.6-1.62-3.6-3.6 s1.62-3.6,3.6-3.6s3.6,1.62,3.6,3.6S13.98,15.6,12,15.6z"/></svg>
                    </span>
                    Configurações
                </a>
            @endif

        </nav>

        {{-- User info + logout --}}
        <div class="sidebar-user">
            @php
                $initials = collect(explode(' ', Auth::user()->name))->map(fn($w) => strtoupper($w[0]))->take(2)->implode('');
            @endphp
            <div class="sidebar-user-avatar">{{ $initials }}</div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name">{{ Auth::user()->name }}</div>
                <div class="sidebar-user-role">{{ Auth::user()->role->label() }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="sidebar-logout-btn" title="Sair do sistema">
                    <svg viewBox="0 0 24 24"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>
                </button>
            </form>
        </div>

    </aside>

    {{-- ── Content wrapper ──────────────────────────────────── --}}
    <div class="content-wrapper">

        {{-- Mobile top bar --}}
        <header class="mobile-topbar">
            <div class="mobile-topbar-brand">
                <div class="mobile-topbar-logo">
                    <svg viewBox="0 0 24 24"><path d="M12 2L2 7v10l10 5 10-5V7L12 2zm0 2.18L20 8.5v7L12 19.82 4 15.5v-7L12 4.18z"/></svg>
                </div>
                <span class="mobile-topbar-name">VICZ</span>
            </div>
            <button class="hamburger-btn" onclick="openSidebar()" aria-label="Abrir menu">
                <svg viewBox="0 0 24 24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
            </button>
        </header>

        {{-- Main content --}}
        <main class="content-area">
            @if (session('success'))
                <div class="flash flash-success" role="alert">
                    <svg class="flash-icon" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="flash flash-error" role="alert">
                    <svg class="flash-icon" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </main>

    </div>

</div>

{{-- ── Mobile bottom navigation ─────────────────────────── --}}
<nav class="bottom-nav" aria-label="Navegação rápida">
    <div class="bottom-nav-items">
        <a href="{{ route('dashboard') }}"
           class="bottom-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
            Início
        </a>

        @can('viewAny', \App\Models\WorkOrder::class)
            <a href="{{ route('work-orders.index') }}"
               class="bottom-nav-item {{ request()->routeIs('work-orders.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24"><path d="M22.7 19l-9.1-9.1a7.006 7.006 0 0 0-8.6-8.6L8.5 4.8 4.8 8.5 1.3 5A7 7 0 0 0 9.9 13.6L19 22.7c.39.39 1.02.39 1.41 0l2.3-2.3c.38-.38.38-1.02-.01-1.4z"/></svg>
                OS
            </a>
        @endcan

        @can('viewAny', \App\Models\AccountPayable::class)
            <a href="{{ route('payables.index') }}"
               class="bottom-nav-item {{ request()->routeIs('payables.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
                A Pagar
            </a>
        @elsecan('viewAny', \App\Models\Proposal::class)
            <a href="{{ route('proposals.index') }}"
               class="bottom-nav-item {{ request()->routeIs('proposals.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                Propostas
            </a>
        @endcan

        @can('viewAny', \App\Models\Alert::class)
            <a href="{{ route('alerts.index') }}"
               class="bottom-nav-item {{ request()->routeIs('alerts.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>
                Alertas
            </a>
        @endcan

        <button class="bottom-nav-item" onclick="openSidebar()" aria-label="Menu completo">
            <svg viewBox="0 0 24 24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
            Menu
        </button>
    </div>
</nav>

{{-- Sidebar toggle script --}}
<script>
(function() {
    var sidebar  = document.getElementById('sidebar');
    var overlay  = document.getElementById('sidebar-overlay');

    window.openSidebar  = function() {
        sidebar.classList.add('open');
        overlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    };

    window.closeSidebar = function() {
        sidebar.classList.remove('open');
        overlay.classList.remove('open');
        document.body.style.overflow = '';
    };

    // Close on Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeSidebar();
    });
})();
</script>

@stack('scripts')
</body>
</html>
