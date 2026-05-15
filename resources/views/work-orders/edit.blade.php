@extends('layouts.app')

@section('title', 'Editar Ordem de Servico - VICZ Implementos')

@section('content')
    <x-page-header
        title="Editar {{ $workOrder->number }}"
        subtitle="Ajuste operacional e financeiro"
    >
        <a href="{{ route('work-orders.show', $workOrder) }}" class="btn btn-ghost">Ver detalhe</a>
    </x-page-header>

    <x-card title="Atualizar Dados da Ordem de Serviço">
        <form method="POST" action="{{ route('work-orders.update', $workOrder) }}">
            @csrf
            @method('PUT')

            @if($errors->any())
                <x-error-state
                    title="Não foi possível atualizar"
                    message="Revise os campos obrigatórios antes de salvar as alterações."
                />
            @endif

            @include('work-orders.partials.form', ['workOrder' => $workOrder])

            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:12px;">
                <a href="{{ route('work-orders.show', $workOrder) }}" class="btn btn-ghost">Cancelar</a>
                <button type="submit" class="btn btn-primary">Salvar alterações</button>
            </div>
        </form>
    </x-card>
@endsection
