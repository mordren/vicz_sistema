@extends('layouts.app')

@section('title', 'Configurações — VICZ Implementos')

@section('content')

<x-page-header title="Configurações" subtitle="Parâmetros e preferências do sistema" />

<div style="display:grid;grid-template-columns:220px 1fr;gap:24px;align-items:start;">

    {{-- Sidebar nav --}}
    <div class="card" style="padding:8px 0;">
        <nav>
            <a href="#geral" class="settings-nav-item active">Geral</a>
            <a href="#usuarios" class="settings-nav-item">Usuários</a>
            <a href="#alertas" class="settings-nav-item">Alertas</a>
            <a href="#integracao" class="settings-nav-item">Integração</a>
        </nav>
    </div>

    {{-- Content area --}}
    <x-card title="Configurações Gerais">
        <x-slot>
            <x-empty-state
                icon="default"
                title="Em desenvolvimento"
                message="Esta seção estará disponível em breve."
            />
        </x-slot>
    </x-card>

</div>

@endsection

@push('styles')
<style>
.settings-nav-item {
    display: block;
    padding: 10px 20px;
    font-size: 14px;
    color: var(--on-surface-variant);
    text-decoration: none;
    border-left: 3px solid transparent;
    transition: background 0.15s, color 0.15s;
}
.settings-nav-item:hover,
.settings-nav-item.active {
    background: var(--surface-container);
    color: var(--primary);
    border-left-color: var(--primary);
    font-weight: 500;
}
</style>
@endpush
