<?php

namespace App\Http\Controllers;

use App\Enums\CustomerStatus;
use App\Enums\CustomerType;
use App\Enums\ReceivableStatus;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\AccountReceivable;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Customer::class);

        $filters = [
            'search' => (string) $request->string('search'),
            'cpf' => preg_replace('/\D+/', '', (string) $request->string('cpf')->toString()),
            'cnpj' => preg_replace('/\D+/', '', (string) $request->string('cnpj')->toString()),
            'phone' => preg_replace('/\D+/', '', (string) $request->string('phone')->toString()),
            'status' => (string) $request->string('status'),
            'type' => (string) $request->string('type'),
            'overdue_only' => (bool) $request->boolean('overdue_only'),
        ];

        $query = Customer::query()->orderBy('name');

        if ($filters['search'] !== '') {
            $query->where(function ($inner) use ($filters): void {
                $inner->where('name', 'like', '%'.$filters['search'].'%')
                    ->orWhere('legal_name', 'like', '%'.$filters['search'].'%')
                    ->orWhere('email', 'like', '%'.$filters['search'].'%');
            });
        }

        if ($filters['cpf'] !== '') {
            $query->where('cpf', $filters['cpf']);
        }

        if ($filters['cnpj'] !== '') {
            $query->where('cnpj', $filters['cnpj']);
        }

        if ($filters['phone'] !== '') {
            $query->where(function ($inner) use ($filters): void {
                $inner->where('phone', $filters['phone'])
                    ->orWhere('whatsapp', $filters['phone'])
                    ->orWhere('secondary_phone', $filters['phone']);
            });
        }

        if (($status = CustomerStatus::tryFrom($filters['status'])) !== null) {
            $query->where('status', $status);
        }

        if (($type = CustomerType::tryFrom($filters['type'])) !== null) {
            $query->where('type', $type);
        }

        if ($filters['overdue_only']) {
            $query->whereHas('receivables', function ($receivables): void {
                $receivables->where('status', ReceivableStatus::OVERDUE);
            });
        }

        $customers = $query
            ->withCount([
                'workOrders',
                'proposals',
                'receivables as overdue_receivables_count' => fn ($q) => $q->where('status', ReceivableStatus::OVERDUE),
            ])
            ->paginate(20)
            ->withQueryString();

        return view('customers.index', [
            'customers' => $customers,
            'filters' => $filters,
            'statuses' => CustomerStatus::cases(),
            'types' => CustomerType::cases(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Customer::class);

        return view('customers.create', [
            'statuses' => CustomerStatus::cases(),
            'types' => CustomerType::cases(),
        ]);
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $payload['created_by'] = $request->user()->id;
        $payload['updated_by'] = $request->user()->id;

        $customer = Customer::query()->create($payload);

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', 'Cliente criado com sucesso.');
    }

    public function show(Customer $customer): View
    {
        $this->authorize('view', $customer);

        $customer->load([
            'attachments.uploadedBy:id,name',
            'workOrders' => fn ($q) => $q->orderByDesc('opened_at')->limit(10),
            'proposals' => fn ($q) => $q->orderByDesc('sent_at')->limit(10),
            'receivables' => fn ($q) => $q->orderByDesc('expected_date')->limit(20),
        ]);

        $openReceivables = $customer->receivables->whereIn('status', [ReceivableStatus::EXPECTED, ReceivableStatus::OVERDUE]);
        $receivedReceivables = $customer->receivables->where('status', ReceivableStatus::RECEIVED);
        $overdueReceivables = $customer->receivables->where('status', ReceivableStatus::OVERDUE);

        $summary = [
            'total_contracted' => (float) $customer->workOrders()->sum('contracted_amount'),
            'total_received' => (float) $receivedReceivables->sum('received_amount'),
            'total_overdue' => (float) $overdueReceivables->sum('expected_amount'),
            'pending_balance' => (float) $openReceivables->sum('expected_amount'),
        ];

        $history = [
            'open_work_orders' => $customer->workOrders->filter(fn ($wo) => ! in_array($wo->status->value, ['DELIVERED', 'CANCELLED'], true)),
            'delivered_work_orders' => $customer->workOrders->filter(fn ($wo) => $wo->status->value === 'DELIVERED'),
            'open_proposals' => $customer->proposals->filter(fn ($proposal) => in_array($proposal->status->value, ['SENT', 'NEGOTIATING'], true)),
            'won_lost_proposals' => $customer->proposals->filter(fn ($proposal) => in_array($proposal->status->value, ['APPROVED', 'LOST'], true)),
            'open_receivables' => $openReceivables,
            'overdue_receivables' => $overdueReceivables,
            'received_history' => $receivedReceivables,
        ];

        return view('customers.show', [
            'customer' => $customer,
            'summary' => $summary,
            'history' => $history,
        ]);
    }

    public function edit(Customer $customer): View
    {
        $this->authorize('update', $customer);

        return view('customers.edit', [
            'customer' => $customer,
            'statuses' => CustomerStatus::cases(),
            'types' => CustomerType::cases(),
        ]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $this->authorize('update', $customer);

        $payload = $request->validated();
        $payload['updated_by'] = $request->user()->id;

        $customer->update($payload);

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', 'Cliente atualizado com sucesso.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->authorize('delete', $customer);

        if (
            $customer->workOrders()->exists()
            || $customer->proposals()->exists()
            || $customer->receivables()->exists()
        ) {
            return redirect()
                ->route('customers.show', $customer)
                ->with('error', 'Cliente com historico vinculado nao pode ser removido.');
        }

        $customer->delete();

        return redirect()
            ->route('customers.index')
            ->with('success', 'Cliente removido com sucesso.');
    }

}
