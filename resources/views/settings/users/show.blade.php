@extends('layouts.app')

@section('title', 'Detalhes do Usuário')

@section('content')
<div class="page-container">
    <div class="page-header">
        <h1 class="page-title">Detalhes do Usuário</h1>
        <a href="{{ route('settings.users.index') }}" class="btn btn-outline">Voltar</a>
    </div>

    <div class="card" style="max-width: 920px; border: 1px solid #e9ecef; border-radius: 8px; margin-bottom: 16px;">
        <div style="display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px;">
            <div>
                <p style="margin:0 0 4px; color:#45464e; font-size:12px; font-weight:600; letter-spacing:.05em;">NOME</p>
                <p style="margin:0; color:#191c1d;">{{ $user->name }}</p>
            </div>
            <div>
                <p style="margin:0 0 4px; color:#45464e; font-size:12px; font-weight:600; letter-spacing:.05em;">EMAIL</p>
                <p style="margin:0; color:#191c1d;">{{ $user->email }}</p>
            </div>
            <div>
                <p style="margin:0 0 4px; color:#45464e; font-size:12px; font-weight:600; letter-spacing:.05em;">ROLE</p>
                <p style="margin:0; color:#191c1d;">{{ $user->role->label() }}</p>
            </div>
            <div>
                <p style="margin:0 0 4px; color:#45464e; font-size:12px; font-weight:600; letter-spacing:.05em;">STATUS</p>
                <p style="margin:0; color:#191c1d;">{{ $user->active ? 'Ativo' : 'Inativo' }}</p>
            </div>
        </div>
    </div>

    <div class="card" style="max-width: 920px; border: 1px solid #e9ecef; border-radius: 8px;">
        <h3 style="margin-top:0; margin-bottom:16px; color:#1c294b;">Auditoria recente</h3>

        @if($user->auditLogs->count())
            <table class="table">
                <thead>
                    <tr>
                        <th>Ação</th>
                        <th>Descrição</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($user->auditLogs->take(20) as $log)
                        <tr>
                            <td>{{ $log->action }}</td>
                            <td>{{ $log->description }}</td>
                            <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="margin:0; color:#45464e;">Nenhum registro de auditoria encontrado para este usuário.</p>
        @endif
    </div>
</div>
@endsection
