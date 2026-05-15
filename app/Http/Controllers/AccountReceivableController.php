<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\ReceivableStatus;
use App\Enums\ReceivableType;
use App\Http\Requests\MarkAccountReceivableAsReceivedRequest;
use App\Http\Requests\StoreAccountReceivableRequest;
use App\Http\Requests\UpdateAccountReceivableRequest;
use App\Models\AccountReceivable;
use App\Models\WorkOrder;
use App\Services\AccountReceivableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountReceivableController extends Controller
{
    public function __construct(private readonly AccountReceivableService $service)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', AccountReceivable::class);

        $this->service->syncOverdueStatuses();

        $query = AccountReceivable::query()
            ->with(['workOrder:id,number,customer'])
            ->orderByRaw("CASE status WHEN 'OVERDUE' THEN 0 WHEN 'EXPECTED' THEN 1 WHEN 'RECEIVED' THEN 2 ELSE 3 END")
            ->orderBy('expected_date')
            ->orderByDesc('id');

        $filters = [
            'customer' => (string) $request->string('customer'),
            'status' => (string) $request->string('status'),
            'expected_from' => (string) $request->string('expected_from'),
            'expected_to' => (string) $request->string('expected_to'),
            'payment_method' => (string) $request->string('payment_method'),
            'work_order_id' => (string) $request->string('work_order_id'),
        ];

        if ($filters['customer'] !== '') {
            $query->where('customer', 'like', '%' . $filters['customer'] . '%');
        }

        if (($status = ReceivableStatus::tryFrom($filters['status'])) !== null) {
            $query->where('status', $status);
        }

        if ($filters['expected_from'] !== '') {
            $query->whereDate('expected_date', '>=', $filters['expected_from']);
        }

        if ($filters['expected_to'] !== '') {
            $query->whereDate('expected_date', '<=', $filters['expected_to']);
        }

        if (($paymentMethod = PaymentMethod::tryFrom($filters['payment_method'])) !== null) {
            $query->where('payment_method', $paymentMethod);
        }

        if ($filters['work_order_id'] !== '') {
            $query->where('work_order_id', (int) $filters['work_order_id']);
        }

        $receivables = $query->paginate(15)->withQueryString();

        $todayExpected = (float) AccountReceivable::query()
            ->where('status', ReceivableStatus::EXPECTED)
            ->whereDate('expected_date', now()->toDateString())
            ->sum('expected_amount');

        $next7Expected = (float) AccountReceivable::query()
            ->where('status', ReceivableStatus::EXPECTED)
            ->whereBetween('expected_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
            ->sum('expected_amount');

        $overdueTotal = (float) AccountReceivable::query()
            ->where('status', ReceivableStatus::OVERDUE)
            ->sum('expected_amount');

        $receivedQuery = AccountReceivable::query()->where('status', ReceivableStatus::RECEIVED);
        if ($filters['expected_from'] !== '') {
            $receivedQuery->whereDate('received_date', '>=', $filters['expected_from']);
        }
        if ($filters['expected_to'] !== '') {
            $receivedQuery->whereDate('received_date', '<=', $filters['expected_to']);
        }

        $receivedInPeriod = (float) $receivedQuery->sum('received_amount');

        $openCustomerBalance = (float) AccountReceivable::query()
            ->whereIn('status', [ReceivableStatus::EXPECTED, ReceivableStatus::OVERDUE])
            ->sum('expected_amount');

        $openCustomersCount = (int) AccountReceivable::query()
            ->whereIn('status', [ReceivableStatus::EXPECTED, ReceivableStatus::OVERDUE])
            ->distinct('customer')
            ->count('customer');

        $overdueCustomersQuery = AccountReceivable::query()
            ->selectRaw('customer, COUNT(*) as receivables_count, SUM(expected_amount) as overdue_total')
            ->where('status', ReceivableStatus::OVERDUE);

        if ($filters['customer'] !== '') {
            $overdueCustomersQuery->where('customer', 'like', '%' . $filters['customer'] . '%');
        }

        if ($filters['expected_from'] !== '') {
            $overdueCustomersQuery->whereDate('expected_date', '>=', $filters['expected_from']);
        }

        if ($filters['expected_to'] !== '') {
            $overdueCustomersQuery->whereDate('expected_date', '<=', $filters['expected_to']);
        }

        if (($paymentMethod = PaymentMethod::tryFrom($filters['payment_method'])) !== null) {
            $overdueCustomersQuery->where('payment_method', $paymentMethod);
        }

        if ($filters['work_order_id'] !== '') {
            $overdueCustomersQuery->where('work_order_id', (int) $filters['work_order_id']);
        }

        if (($status = ReceivableStatus::tryFrom($filters['status'])) !== null && $status !== ReceivableStatus::OVERDUE) {
            $overdueCustomersQuery->whereRaw('1 = 0');
        }

        $overdueCustomers = $overdueCustomersQuery
            ->groupBy('customer')
            ->orderByDesc('overdue_total')
            ->limit(5)
            ->get();

        $workOrders = WorkOrder::query()
            ->select(['id', 'number', 'customer'])
            ->orderByDesc('opened_at')
            ->limit(300)
            ->get();

        return view('receivables.index', [
            'receivables' => $receivables,
            'overdueCustomers' => $overdueCustomers,
            'workOrders' => $workOrders,
            'filters' => $filters,
            'statuses' => ReceivableStatus::cases(),
            'paymentMethods' => PaymentMethod::cases(),
            'summaryCards' => [
                'todayExpected' => $todayExpected,
                'next7Expected' => $next7Expected,
                'overdueTotal' => $overdueTotal,
                'receivedInPeriod' => $receivedInPeriod,
                'openCustomerBalance' => $openCustomerBalance,
                'openCustomersCount' => $openCustomersCount,
            ],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', AccountReceivable::class);

        $workOrders = WorkOrder::query()
            ->select(['id', 'number', 'customer'])
            ->orderByDesc('opened_at')
            ->limit(300)
            ->get();

        return view('receivables.create', [
            'workOrders' => $workOrders,
            'types' => ReceivableType::cases(),
        ]);
    }

    public function store(StoreAccountReceivableRequest $request): RedirectResponse
    {
        $receivable = $this->service->create($request->validated(), $request->user());

        return redirect()
            ->route('receivables.show', $receivable)
            ->with('success', 'Conta a receber criada com sucesso.');
    }

    public function show(AccountReceivable $receivable): View
    {
        $this->authorize('view', $receivable);

        $this->service->syncOneReceivableOverdueStatus($receivable);

        $receivable->load([
            'workOrder:id,number,customer,contracted_amount,received_amount,receivable_balance',
            'attachments.uploadedBy:id,name',
        ]);

        return view('receivables.show', [
            'receivable' => $receivable,
            'paymentMethods' => PaymentMethod::cases(),
        ]);
    }

    public function edit(AccountReceivable $receivable): View|RedirectResponse
    {
        $this->authorize('update', $receivable);

        if (in_array($receivable->status, [ReceivableStatus::RECEIVED, ReceivableStatus::CANCELLED], true)) {
            return redirect()
                ->route('receivables.show', $receivable)
                ->with('error', 'Somente recebíveis previstos ou em atraso podem ser editados.');
        }

        $workOrders = WorkOrder::query()
            ->select(['id', 'number', 'customer'])
            ->orderByDesc('opened_at')
            ->limit(300)
            ->get();

        return view('receivables.edit', [
            'receivable' => $receivable,
            'workOrders' => $workOrders,
            'types' => ReceivableType::cases(),
        ]);
    }

    public function update(UpdateAccountReceivableRequest $request, AccountReceivable $receivable): RedirectResponse
    {
        $this->authorize('update', $receivable);

        if (in_array($receivable->status, [ReceivableStatus::RECEIVED, ReceivableStatus::CANCELLED], true)) {
            return redirect()
                ->route('receivables.show', $receivable)
                ->with('error', 'Recebíveis recebidos ou cancelados não podem ser editados.');
        }

        $this->service->update($receivable, $request->validated(), $request->user());

        return redirect()
            ->route('receivables.show', $receivable)
            ->with('success', 'Conta a receber atualizada com sucesso.');
    }

    public function markReceived(MarkAccountReceivableAsReceivedRequest $request, AccountReceivable $receivable): RedirectResponse
    {
        $this->authorize('markAsReceived', $receivable);

        if ($receivable->status === ReceivableStatus::CANCELLED) {
            return redirect()
                ->route('receivables.show', $receivable)
                ->with('error', 'Não é possível receber um lançamento cancelado.');
        }

        $data = $request->validated();

        $this->service->markAsReceived(
            $receivable,
            (string) $data['received_date'],
            (float) $data['received_amount'],
            PaymentMethod::from((string) $data['payment_method']),
            $data['notes'] ?? null,
            $request->file('receipt_file'),
            $request->user()
        );

        return redirect()
            ->route('receivables.show', $receivable)
            ->with('success', 'Recebível marcado como recebido.');
    }

    public function cancel(Request $request, AccountReceivable $receivable): RedirectResponse
    {
        $this->authorize('update', $receivable);

        if ($receivable->status === ReceivableStatus::RECEIVED) {
            return redirect()
                ->route('receivables.show', $receivable)
                ->with('error', 'Recebível já recebido não pode ser cancelado.');
        }

        $request->validate([
            'cancel_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->service->cancel($receivable, $request->user(), $request->input('cancel_reason'));

        return redirect()
            ->route('receivables.show', $receivable)
            ->with('success', 'Recebível cancelado com sucesso.');
    }

    public function destroy(AccountReceivable $receivable): RedirectResponse
    {
        $this->authorize('update', $receivable);

        if ($receivable->status === ReceivableStatus::RECEIVED) {
            return redirect()
                ->route('receivables.show', $receivable)
                ->with('error', 'Recebível já recebido não pode ser cancelado.');
        }

        $this->service->cancel($receivable, auth()->user());

        return redirect()
            ->route('receivables.index')
            ->with('success', 'Recebível cancelado com sucesso.');
    }

}
