<div class="db-page-header">
    <div class="db-page-header__body">
        <div class="db-page-header__text">
            <h1 class="db-page-header__title">Painel de Gestão</h1>
            <p class="db-page-header__subtitle">Resumo operacional, financeiro e comercial da VICZ Implementos</p>
        </div>
        <div class="db-page-header__meta">
            <span class="db-page-header__date">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg>
                {{ now()->translatedFormat('l, d \d\e F \d\e Y') }}
            </span>
        </div>
    </div>

    <div class="db-page-header__actions">
        <form method="POST" action="{{ route('alerts.generate') }}" style="display:inline;">
            @csrf
            <button type="submit" class="btn btn-ghost btn-sm">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5S10.5 3.17 10.5 4v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>
                Gerar alertas
            </button>
        </form>

        @can('create', \App\Models\WorkOrder::class)
            <a href="{{ route('work-orders.create') }}" class="btn btn-ghost btn-sm">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-2 10h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/></svg>
                Nova OS
            </a>
        @endcan

        @can('create', \App\Models\Proposal::class)
            <a href="{{ route('proposals.create') }}" class="btn btn-ghost btn-sm">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                Nova Proposta
            </a>
        @endcan

        @can('create', \App\Models\Customer::class)
            <a href="{{ route('customers.create') }}" class="btn btn-ghost btn-sm">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor"><path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                Novo Cliente
            </a>
        @endcan

        @can('create', \App\Models\AccountReceivable::class)
            <a href="{{ route('receivables.create') }}" class="btn btn-primary btn-sm">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor"><path d="M12 2v10.17l3.59-3.58L17 10l-5 5-5-5 1.41-1.41L11 12.17V2zM5 18h14v2H5z"/></svg>
                Registrar Recebimento
            </a>
        @endcan

        @can('create', \App\Models\AccountPayable::class)
            <a href="{{ route('payables.create') }}" class="btn btn-secondary btn-sm">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor"><path d="M12 22V11.83l-3.59 3.58L7 14l5-5 5 5-1.41 1.41L13 11.83V22zM5 4h14v2H5z"/></svg>
                Registrar Pagamento
            </a>
        @endcan
    </div>
</div>
