@extends('layouts.app')

@section('title', 'Log de Cron Jobs')

@section('content')
<div class="page-container">

    <div class="page-header">
        <div>
            <h1 class="page-title">Log de Cron Jobs</h1>
            <p class="page-subtitle">Histórico das últimas execuções automáticas</p>
        </div>
        <a href="{{ route('settings.index') }}" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" style="width:16px;height:16px;"><path fill="currentColor" d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
            Voltar
        </a>
    </div>

    <div class="cron-log-card">
        <div class="cron-log-header">
            <div class="cron-log-header-left">
                <svg viewBox="0 0 24 24" style="width:20px;height:20px;flex-shrink:0;"><path fill="currentColor" d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67V7z"/></svg>
                <span>{{ count($lines) }} registro(s) — ordem decrescente</span>
            </div>
            <button onclick="window.location.reload()" class="btn-refresh">
                <svg viewBox="0 0 24 24" style="width:16px;height:16px;"><path fill="currentColor" d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg>
                Atualizar
            </button>
        </div>

        @if(count($lines) === 0)
            <div class="cron-log-empty">
                <svg viewBox="0 0 24 24" style="width:48px;height:48px;opacity:0.3;"><path fill="currentColor" d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67V7z"/></svg>
                <p>Nenhuma execução registrada ainda.</p>
                <span>O log será preenchido após o primeiro disparo do cron.</span>
            </div>
        @else
            <div class="cron-log-table-wrap">
                <table class="cron-log-table">
                    <thead>
                        <tr>
                            <th>Data / Hora</th>
                            <th>Tipo</th>
                            <th>Duração</th>
                            <th>Resultado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($lines as $line)
                            @php
                                // formato: "17/05/2026 08:00:01 | email  | 421ms | OK recipients=2"
                                $parts   = array_map('trim', explode('|', $line, 4));
                                $date    = $parts[0] ?? $line;
                                $type    = $parts[1] ?? '—';
                                $elapsed = $parts[2] ?? '—';
                                $status  = $parts[3] ?? '—';
                                $isError = str_starts_with(strtolower($status), 'erro') || str_starts_with($status, '✘');
                            @endphp
                            <tr class="{{ $isError ? 'row-error' : 'row-ok' }}">
                                <td class="cell-date">{{ $date }}</td>
                                <td>
                                    @if(str_contains($type, 'email'))
                                        <span class="badge badge-email">E-mail</span>
                                    @else
                                        <span class="badge badge-alerts">Alertas</span>
                                    @endif
                                </td>
                                <td class="cell-elapsed">{{ $elapsed }}</td>
                                <td class="cell-status">
                                    @if($isError)
                                        <span class="status-icon status-error">
                                            <svg viewBox="0 0 24 24" style="width:14px;height:14px;"><path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                                        </span>
                                    @else
                                        <span class="status-icon status-ok">
                                            <svg viewBox="0 0 24 24" style="width:14px;height:14px;"><path fill="currentColor" d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                                        </span>
                                    @endif
                                    {{ $status }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>

<style>
.cron-log-card {
    background: #ffffff;
    border: 1px solid #e1e3e4;
    border-radius: 12px;
    overflow: hidden;
    margin-top: 24px;
}

.cron-log-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 24px;
    border-bottom: 1px solid #e1e3e4;
    background: #f8f9fa;
}

.cron-log-header-left {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: #45464e;
}

.btn-refresh {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    font-size: 13px;
    font-weight: 500;
    color: #515d82;
    background: transparent;
    border: 1px solid #c6c6cf;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.15s, border-color 0.15s;
}

.btn-refresh:hover {
    background: #edeeef;
    border-color: #515d82;
}

.cron-log-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 64px 24px;
    color: #76777f;
    text-align: center;
}

.cron-log-empty p {
    margin: 0;
    font-size: 16px;
    font-weight: 500;
    color: #45464e;
}

.cron-log-empty span {
    font-size: 14px;
    color: #76777f;
}

.cron-log-table-wrap {
    overflow-x: auto;
}

.cron-log-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

.cron-log-table thead th {
    padding: 12px 20px;
    text-align: left;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #76777f;
    background: #f8f9fa;
    border-bottom: 1px solid #e1e3e4;
}

.cron-log-table tbody tr {
    border-bottom: 1px solid #edeeef;
    transition: background 0.12s;
}

.cron-log-table tbody tr:last-child {
    border-bottom: none;
}

.cron-log-table tbody tr:hover {
    background: #f8f9fa;
}

.cron-log-table tbody tr.row-error {
    background: #fff5f5;
}

.cron-log-table tbody tr.row-error:hover {
    background: #ffecec;
}

.cron-log-table td {
    padding: 12px 20px;
    color: #191c1d;
    vertical-align: middle;
}

.cell-date {
    white-space: nowrap;
    font-family: 'IBM Plex Mono', monospace;
    font-size: 13px;
    color: #45464e;
}

.cell-elapsed {
    white-space: nowrap;
    font-size: 13px;
    color: #76777f;
}

.cell-status {
    display: flex;
    align-items: center;
    gap: 6px;
}

.badge {
    display: inline-flex;
    align-items: center;
    padding: 2px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
}

.badge-email {
    background: rgba(81, 93, 130, 0.12);
    color: #515d82;
}

.badge-alerts {
    background: rgba(0, 109, 65, 0.1);
    color: #006d41;
}

.status-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    flex-shrink: 0;
}

.status-ok {
    background: rgba(25, 135, 84, 0.12);
    color: #198754;
}

.status-error {
    background: rgba(186, 26, 26, 0.1);
    color: #ba1a1a;
}
</style>
@endsection
