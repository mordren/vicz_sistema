@extends('layouts.app')

@section('title', 'Configurações e Administração')

@section('content')
<div class="page-container">
    <div class="page-header">
        <div>
            <h1 class="page-title">Configurações</h1>
            <p class="page-subtitle">Administração do sistema VICZ Implementos</p>
        </div>
    </div>

    <div class="settings-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; margin-top: 32px;">

        {{-- Users --}}
        <a href="{{ route('settings.users.index') }}" class="settings-card">
            <div class="settings-card-icon" style="background-color: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                <svg viewBox="0 0 24 24" style="width: 32px; height: 32px;"><path fill="currentColor" d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            </div>
            <div class="settings-card-content">
                <h3 class="settings-card-title">Usuários</h3>
                <p class="settings-card-description">Gerenciar usuários, roles e acesso</p>
            </div>
        </a>

        {{-- Permissions --}}
        <a href="{{ route('settings.permissions') }}" class="settings-card">
            <div class="settings-card-icon" style="background-color: rgba(34, 197, 94, 0.1); color: #22c55e;">
                <svg viewBox="0 0 24 24" style="width: 32px; height: 32px;"><path fill="currentColor" d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
            </div>
            <div class="settings-card-content">
                <h3 class="settings-card-title">Permissões</h3>
                <p class="settings-card-description">Visualizar roles e permissões</p>
            </div>
        </a>

        {{-- Company --}}
        <a href="{{ route('settings.company') }}" class="settings-card">
            <div class="settings-card-icon" style="background-color: rgba(168, 85, 247, 0.1); color: #a855f7;">
                <svg viewBox="0 0 24 24" style="width: 32px; height: 32px;"><path fill="currentColor" d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm4 8H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V9h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2z"/></svg>
            </div>
            <div class="settings-card-content">
                <h3 class="settings-card-title">Empresa</h3>
                <p class="settings-card-description">Dados e configurações da empresa</p>
            </div>
        </a>

        {{-- Financial --}}
        <a href="{{ route('settings.financial') }}" class="settings-card">
            <div class="settings-card-icon" style="background-color: rgba(249, 115, 22, 0.1); color: #f97316;">
                <svg viewBox="0 0 24 24" style="width: 32px; height: 32px;"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
            </div>
            <div class="settings-card-content">
                <h3 class="settings-card-title">Financeiro</h3>
                <p class="settings-card-description">Saldo, categorias, regras</p>
            </div>
        </a>

        {{-- Work Orders --}}
        <a href="{{ route('settings.work-order') }}" class="settings-card">
            <div class="settings-card-icon" style="background-color: rgba(14, 165, 233, 0.1); color: #0ea5e9;">
                <svg viewBox="0 0 24 24" style="width: 32px; height: 32px;"><path fill="currentColor" d="M19 13H5v-2h14v2z"/></svg>
            </div>
            <div class="settings-card-content">
                <h3 class="settings-card-title">Orden de Serviço</h3>
                <p class="settings-card-description">Padrões e automações</p>
            </div>
        </a>

        {{-- Proposals --}}
        <a href="{{ route('settings.proposal') }}" class="settings-card">
            <div class="settings-card-icon" style="background-color: rgba(236, 72, 153, 0.1); color: #ec4899;">
                <svg viewBox="0 0 24 24" style="width: 32px; height: 32px;"><path fill="currentColor" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-8-6z"/></svg>
            </div>
            <div class="settings-card-content">
                <h3 class="settings-card-title">Propostas</h3>
                <p class="settings-card-description">Configuração de propostas</p>
            </div>
        </a>

        {{-- Alerts --}}
        <a href="{{ route('settings.alert') }}" class="settings-card">
            <div class="settings-card-icon" style="background-color: rgba(239, 68, 68, 0.1); color: #ef4444;">
                <svg viewBox="0 0 24 24" style="width: 32px; height: 32px;"><path fill="currentColor" d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>
            </div>
            <div class="settings-card-content">
                <h3 class="settings-card-title">Alertas</h3>
                <p class="settings-card-description">Regras e notificações</p>
            </div>
        </a>

        {{-- Attachments --}}
        <a href="{{ route('settings.attachment') }}" class="settings-card">
            <div class="settings-card-icon" style="background-color: rgba(107, 114, 128, 0.1); color: #6b7280;">
                <svg viewBox="0 0 24 24" style="width: 32px; height: 32px;"><path fill="currentColor" d="M19.35 10.04C18.67 6.59 15.64 4 12 4c-1.48 0-2.85.43-4.01 1.17l1.46 1.46C10.21 5.23 11.08 5 12 5c3.04 0 5.5 2.46 5.5 5.5v.5H19c2.05 0 3.71 1.66 3.71 3.71 0 1.99-1.57 3.63-3.51 3.66-.04-.01-.09-.01-.15-.01h-1.15l1.46-1.46C23.88 15.61 24 14.84 24 14c0-3.31-2.64-6-5.9-6-.34 0-.67.03-1.01.07zM19 13h-2v3h-3v2h3v3h2v-3h3v-2h-3v-3z"/></svg>
            </div>
            <div class="settings-card-content">
                <h3 class="settings-card-title">Anexos</h3>
                <p class="settings-card-description">Tipos e tamanhos permitidos</p>
            </div>
        </a>

    </div>

    <style>
        .settings-grid {
            margin-top: 32px;
        }

        .settings-card {
            display: flex;
            gap: 20px;
            padding: 24px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            background-color: white;
            text-decoration: none;
            color: inherit;
            transition: all 0.2s ease;
        }

        .settings-card:hover {
            border-color: #d1d5db;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
            transform: translateY(-2px);
        }

        .settings-card-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 56px;
            width: 56px;
            height: 56px;
            border-radius: 8px;
            flex-shrink: 0;
        }

        .settings-card-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .settings-card-title {
            margin: 0 0 8px 0;
            font-size: 16px;
            font-weight: 600;
            color: #111827;
        }

        .settings-card-description {
            margin: 0;
            font-size: 14px;
            color: #6b7280;
        }
    </style>

</div>
@endsection


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
