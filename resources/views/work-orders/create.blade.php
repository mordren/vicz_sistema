@extends('layouts.app')

@section('title', 'Nova Ordem de Servico - VICZ Implementos')

@section('content')
    <x-page-header
        title="Nova Ordem de Servico"
        subtitle="Cadastro operacional e financeiro da OS"
    >
        <a href="{{ route('work-orders.index') }}" class="btn btn-ghost">Voltar</a>
    </x-page-header>

    <x-card title="Dados da Ordem de Serviço">
        <form method="POST" action="{{ route('work-orders.store') }}">
            @csrf

            @if($errors->any())
                <x-error-state
                    title="Não foi possível salvar"
                    message="Revise os campos obrigatórios para concluir o cadastro da ordem de serviço."
                />
            @endif

            @include('work-orders.partials.form')

            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:12px;">
                <a href="{{ route('work-orders.index') }}" class="btn btn-ghost">Cancelar</a>
                <button type="submit" class="btn btn-primary">Criar ordem</button>
            </div>
        </form>
    </x-card>
@endsection
