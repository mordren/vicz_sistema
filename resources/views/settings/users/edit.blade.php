@extends('layouts.app')

@section('title', 'Editar Usuário')

@section('content')
<div class="page-container">
    <div class="page-header">
        <h1 class="page-title">Editar Usuário</h1>
    </div>

    <div class="card" style="max-width: 500px;">
        <form method="POST" action="{{ route('settings.users.update', $user) }}" novalidate>
            @csrf
            @method('PATCH')

            <div class="form-group">
                <label class="form-label">Nome</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label class="form-label">Nova Senha <span style="font-size: 12px; color: #6b7280;">(deixe em branco para não alterar)</span></label>
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror">
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label class="form-label">Confirmar Senha</label>
                <input type="password" name="password_confirmation" class="form-control">
            </div>

            <div class="form-group">
                <label class="form-label">Role</label>
                <select name="role" class="form-control form-select @error('role') is-invalid @enderror" required>
                    @foreach($roles as $role)
                        <option value="{{ $role->value }}" @selected(old('role', $user->role->value) == $role->value)>{{ $role->label() }}</option>
                    @endforeach
                </select>
                @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label class="form-checkbox">
                    <input type="checkbox" name="active" value="1" @checked(old('active', $user->active))>
                    Usuário ativo
                </label>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn btn-primary">Atualizar</button>
                <a href="{{ route('settings.users.index') }}" class="btn btn-outline">Cancelar</a>
            </div>
        </form>
    </div>

</div>
@endsection
