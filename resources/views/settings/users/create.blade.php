@extends('layouts.app')

@section('title', 'Novo Usuário')

@section('content')
<div class="page-container">
    <div class="page-header">
        <h1 class="page-title">Novo Usuário</h1>
    </div>

    <div class="card" style="max-width: 500px;">
        <form method="POST" action="{{ route('settings.users.store') }}" novalidate>
            @csrf

            <div class="form-group">
                <label class="form-label">Nome</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label class="form-label">Senha</label>
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label class="form-label">Confirmar Senha</label>
                <input type="password" name="password_confirmation" class="form-control" required>
            </div>

            <div class="form-group">
                <label class="form-label">Role</label>
                <select name="role" class="form-control form-select @error('role') is-invalid @enderror" required>
                    <option value="">Selecione uma role...</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->value }}" @selected(old('role') == $role->value)>{{ $role->label() }}</option>
                    @endforeach
                </select>
                @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label class="form-checkbox">
                    <input type="checkbox" name="active" value="1" checked>
                    Usuário ativo
                </label>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn btn-primary">Criar Usuário</button>
                <a href="{{ route('settings.users.index') }}" class="btn btn-outline">Cancelar</a>
            </div>
        </form>
    </div>

</div>
@endsection
