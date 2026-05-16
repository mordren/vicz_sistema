<?php

namespace App\Http\Controllers\Settings;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manageUsers', User::class);

        $users = User::query()
            ->orderBy('name')
            ->paginate(20);

        return view('settings.users.index', [
            'users' => $users,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('manageUsers', User::class);

        return view('settings.users.create', [
            'roles' => UserRole::cases(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        Gate::authorize('manageUsers', User::class);

        $data = $request->validated();

        User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
            'active' => $data['active'] ?? true,
        ]);

        return redirect()
            ->route('settings.users.index')
            ->with('success', 'Usuário criado com sucesso.');
    }

    public function edit(User $user): View|RedirectResponse
    {
        Gate::authorize('manageUsers', User::class);

        if ($user->id === auth()->id() && auth()->user()->role !== UserRole::ADMIN) {
            return redirect()
                ->route('settings.users.index')
                ->with('error', 'Você não pode editar sua própria conta de permissões.');
        }

        return view('settings.users.edit', [
            'user' => $user,
            'roles' => UserRole::cases(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('manageUsers', User::class);

        if ($user->id === auth()->id() && auth()->user()->role !== UserRole::ADMIN) {
            return redirect()
                ->route('settings.users.index')
                ->with('error', 'Você não pode editar sua própria conta de permissões.');
        }

        $data = $request->validated();

        $updateData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'active' => $data['active'] ?? $user->active,
        ];

        if (! empty($data['password'])) {
            $updateData['password'] = $data['password'];
        }

        $user->update($updateData);

        return redirect()
            ->route('settings.users.index')
            ->with('success', 'Usuário atualizado com sucesso.');
    }

    public function show(User $user): View
    {
        Gate::authorize('viewSettings', User::class);

        $user->load('auditLogs');

        return view('settings.users.show', [
            'user' => $user,
        ]);
    }

    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('manageUsers', User::class);

        if ($user->id === auth()->id()) {
            return redirect()
                ->route('settings.users.index')
                ->with('error', 'Você não pode desativar sua própria conta.');
        }

        $user->update(['active' => ! $user->active]);

        $status = $user->active ? 'ativado' : 'desativado';

        return redirect()
            ->route('settings.users.index')
            ->with('success', "Usuário {$status} com sucesso.");
    }

    public function permissions(): View
    {
        Gate::authorize('viewSettings', User::class);

        $rolePermissions = [
            'ADMIN' => [
                'Customers' => ['view', 'create', 'edit', 'delete'],
                'Work Orders' => ['view', 'create', 'edit', 'delete', 'mark_delivered', 'cancel'],
                'Proposals' => ['view', 'create', 'edit', 'delete', 'approve', 'convert'],
                'Accounts Receivable' => ['view', 'create', 'edit', 'mark_received', 'cancel'],
                'Accounts Payable' => ['view', 'create', 'edit', 'mark_paid', 'cancel'],
                'Dashboard' => ['view_all'],
                'Reports' => ['view_all', 'export'],
                'Settings' => ['view', 'update'],
                'Users' => ['view', 'manage'],
                'Audit Logs' => ['view'],
            ],
            'FINANCE' => [
                'Customers' => ['view'],
                'Work Orders' => ['view'],
                'Proposals' => ['view'],
                'Accounts Receivable' => ['view', 'create', 'edit', 'mark_received'],
                'Accounts Payable' => ['view', 'create', 'edit', 'mark_paid'],
                'Dashboard' => ['view_financial'],
                'Reports' => ['view_financial', 'export'],
            ],
            'OPERATIONS' => [
                'Customers' => ['view'],
                'Work Orders' => ['view', 'edit', 'mark_delivered'],
                'Proposals' => ['view'],
                'Dashboard' => ['view_operational'],
                'Reports' => ['view_operational'],
            ],
            'SALES' => [
                'Customers' => ['view', 'create', 'edit'],
                'Proposals' => ['view', 'create', 'edit', 'approve'],
                'Dashboard' => ['view_sales'],
                'Reports' => ['view_sales'],
            ],
        ];

        return view('settings.permissions', [
            'rolePermissions' => $rolePermissions,
        ]);
    }
}
