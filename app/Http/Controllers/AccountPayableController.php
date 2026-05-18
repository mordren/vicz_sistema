<?php

namespace App\Http\Controllers;

use App\Enums\PayableCategory;
use App\Enums\PayableStatus;
use App\Enums\PaymentMethod;
use App\Http\Requests\MarkAccountPayableAsPaidRequest;
use App\Http\Requests\StoreAccountPayableRequest;
use App\Http\Requests\UpdateAccountPayableRequest;
use App\Models\AccountPayable;
use App\Models\WorkOrder;
use App\Services\AccountPayableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AccountPayableController extends Controller
{
    public function __construct(private readonly AccountPayableService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', AccountPayable::class);

        $this->service->syncOverdueStatuses();

        $query = AccountPayable::query()
            ->with(['workOrder:id,number,customer'])
            ->orderByRaw("CASE status WHEN 'OVERDUE' THEN 0 WHEN 'OPEN' THEN 1 WHEN 'PAID' THEN 2 ELSE 3 END")
            ->orderBy('due_date')
            ->orderByDesc('id');

        $filters = [
            'supplier' => (string) $request->string('supplier'),
            'status' => (string) $request->string('status'),
            'due_from' => (string) $request->string('due_from'),
            'due_to' => (string) $request->string('due_to'),
            'category' => (string) $request->string('category'),
            'payment_method' => (string) $request->string('payment_method'),
            'work_order_id' => (string) $request->string('work_order_id'),
        ];

        if ($filters['supplier'] !== '') {
            $query->where('supplier', 'like', '%'.$filters['supplier'].'%');
        }

        if (($status = PayableStatus::tryFrom($filters['status'])) !== null) {
            $query->where('status', $status);
        }

        if ($filters['due_from'] !== '') {
            $query->whereDate('due_date', '>=', $filters['due_from']);
        }

        if ($filters['due_to'] !== '') {
            $query->whereDate('due_date', '<=', $filters['due_to']);
        }

        if (($category = PayableCategory::tryFrom($filters['category'])) !== null) {
            $query->where('category', $category);
        }

        if (($paymentMethod = PaymentMethod::tryFrom($filters['payment_method'])) !== null) {
            $query->where('payment_method', $paymentMethod);
        }

        if ($filters['work_order_id'] !== '') {
            $query->where('work_order_id', (int) $filters['work_order_id']);
        }

        $payables = $query->paginate(15)->withQueryString();

        $openTotal = (float) AccountPayable::query()
            ->whereIn('status', [PayableStatus::OPEN, PayableStatus::OVERDUE])
            ->sum('amount');

        $dueToday = (float) AccountPayable::query()
            ->whereIn('status', [PayableStatus::OPEN, PayableStatus::OVERDUE])
            ->whereDate('due_date', now()->toDateString())
            ->sum('amount');

        $dueNext7 = (float) AccountPayable::query()
            ->where('status', PayableStatus::OPEN)
            ->whereBetween('due_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
            ->sum('amount');

        $overdueTotal = (float) AccountPayable::query()
            ->where('status', PayableStatus::OVERDUE)
            ->sum('amount');

        $paidQuery = AccountPayable::query()->where('status', PayableStatus::PAID);
        if ($filters['due_from'] !== '') {
            $paidQuery->whereDate('payment_date', '>=', $filters['due_from']);
        }
        if ($filters['due_to'] !== '') {
            $paidQuery->whereDate('payment_date', '<=', $filters['due_to']);
        }

        $paidInPeriod = (float) $paidQuery->sum('paid_amount');

        $overdueSuppliersQuery = AccountPayable::query()
            ->selectRaw('supplier, COUNT(*) as payables_count, SUM(amount) as overdue_total')
            ->where('status', PayableStatus::OVERDUE);

        if ($filters['supplier'] !== '') {
            $overdueSuppliersQuery->where('supplier', 'like', '%'.$filters['supplier'].'%');
        }

        if ($filters['due_from'] !== '') {
            $overdueSuppliersQuery->whereDate('due_date', '>=', $filters['due_from']);
        }

        if ($filters['due_to'] !== '') {
            $overdueSuppliersQuery->whereDate('due_date', '<=', $filters['due_to']);
        }

        if (($category = PayableCategory::tryFrom($filters['category'])) !== null) {
            $overdueSuppliersQuery->where('category', $category);
        }

        if (($paymentMethod = PaymentMethod::tryFrom($filters['payment_method'])) !== null) {
            $overdueSuppliersQuery->where('payment_method', $paymentMethod);
        }

        if ($filters['work_order_id'] !== '') {
            $overdueSuppliersQuery->where('work_order_id', (int) $filters['work_order_id']);
        }

        if (($status = PayableStatus::tryFrom($filters['status'])) !== null && $status !== PayableStatus::OVERDUE) {
            $overdueSuppliersQuery->whereRaw('1 = 0');
        }

        $overdueSuppliers = $overdueSuppliersQuery
            ->groupBy('supplier')
            ->orderByDesc('overdue_total')
            ->limit(5)
            ->get();

        $workOrders = WorkOrder::query()
            ->select(['id', 'number', 'customer'])
            ->orderByDesc('opened_at')
            ->limit(300)
            ->get();

        return view('payables.index', [
            'payables' => $payables,
            'overdueSuppliers' => $overdueSuppliers,
            'workOrders' => $workOrders,
            'filters' => $filters,
            'statuses' => PayableStatus::cases(),
            'categories' => PayableCategory::cases(),
            'paymentMethods' => PaymentMethod::cases(),
            'summaryCards' => [
                'openTotal' => $openTotal,
                'dueToday' => $dueToday,
                'dueNext7' => $dueNext7,
                'overdueTotal' => $overdueTotal,
                'paidInPeriod' => $paidInPeriod,
            ],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', AccountPayable::class);

        $workOrders = WorkOrder::query()
            ->select(['id', 'number', 'customer'])
            ->orderByDesc('opened_at')
            ->limit(300)
            ->get();

        return view('payables.create', [
            'workOrders' => $workOrders,
            'categories' => PayableCategory::cases(),
            'paymentMethods' => PaymentMethod::cases(),
        ]);
    }

    public function store(StoreAccountPayableRequest $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $result = $this->service->create(
            $request->validated(),
            $request->user(),
            $request->file('receipt_file')
        );

        $message = $result['count'] > 1
            ? 'Conta a pagar criada em '.$result['count'].' parcelas com sucesso.'
            : 'Conta a pagar criada com sucesso.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        $redirectTo = $request->input('redirect_to');
        if ($redirectTo && (str_starts_with($redirectTo, '/') || str_starts_with($redirectTo, config('app.url')))) {
            return redirect($redirectTo)->with('success', $message);
        }

        return redirect()
            ->route('payables.show', $result['firstPayable'])
            ->with('success', $message);
    }

    public function show(AccountPayable $payable): View
    {
        $this->authorize('view', $payable);

        $this->service->syncOnePayableOverdueStatus($payable);

        $payable->load([
            'workOrder:id,number,customer,estimated_cost,actual_cost,estimated_margin,actual_margin',
            'attachments.uploadedBy:id,name',
        ]);

        return view('payables.show', [
            'payable' => $payable,
            'paymentMethods' => PaymentMethod::cases(),
        ]);
    }

    public function edit(AccountPayable $payable): View|RedirectResponse
    {
        $this->authorize('update', $payable);

        if (in_array($payable->status, [PayableStatus::PAID, PayableStatus::CANCELLED], true)) {
            return redirect()
                ->route('payables.show', $payable)
                ->with('error', 'Somente contas em aberto ou vencidas podem ser editadas.');
        }

        $workOrders = WorkOrder::query()
            ->select(['id', 'number', 'customer'])
            ->orderByDesc('opened_at')
            ->limit(300)
            ->get();

        return view('payables.edit', [
            'payable' => $payable,
            'workOrders' => $workOrders,
            'categories' => PayableCategory::cases(),
            'paymentMethods' => PaymentMethod::cases(),
        ]);
    }

    public function update(UpdateAccountPayableRequest $request, AccountPayable $payable): RedirectResponse
    {
        $this->authorize('update', $payable);

        if (in_array($payable->status, [PayableStatus::PAID, PayableStatus::CANCELLED], true)) {
            return redirect()
                ->route('payables.show', $payable)
                ->with('error', 'Contas pagas ou canceladas nao podem ser editadas.');
        }

        $this->service->update($payable, $request->validated(), $request->user());

        return redirect()
            ->route('payables.show', $payable)
            ->with('success', 'Conta a pagar atualizada com sucesso.');
    }

    public function markPaid(MarkAccountPayableAsPaidRequest $request, AccountPayable $payable): RedirectResponse
    {
        $this->authorize('markAsPaid', $payable);

        if ($payable->status === PayableStatus::PAID) {
            return redirect()
                ->route('payables.show', $payable)
                ->with('error', 'Conta ja esta marcada como paga.');
        }

        if ($payable->status === PayableStatus::CANCELLED) {
            return redirect()
                ->route('payables.show', $payable)
                ->with('error', 'Nao e possivel pagar um lancamento cancelado.');
        }

        $data = $request->validated();

        $this->service->markAsPaid(
            $payable,
            (string) $data['payment_date'],
            (float) $data['paid_amount'],
            PaymentMethod::from((string) $data['payment_method']),
            $data['notes'] ?? null,
            $request->file('receipt_file'),
            $request->user()
        );

        Cache::increment('dashboard_finance_version');

        return redirect()
            ->route('payables.show', $payable)
            ->with('success', 'Conta marcada como paga.');
    }

    public function cancel(Request $request, AccountPayable $payable): RedirectResponse
    {
        $this->authorize('update', $payable);

        if ($payable->status === PayableStatus::PAID) {
            return redirect()
                ->route('payables.show', $payable)
                ->with('error', 'Conta ja paga nao pode ser cancelada.');
        }

        $request->validate([
            'cancel_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->service->cancel($payable, $request->user(), $request->input('cancel_reason'));

        Cache::increment('dashboard_finance_version');

        return redirect()
            ->route('payables.show', $payable)
            ->with('success', 'Conta cancelada com sucesso.');
    }

    public function destroy(AccountPayable $payable): RedirectResponse
    {
        $this->authorize('update', $payable);

        if ($payable->status === PayableStatus::PAID) {
            return redirect()
                ->route('payables.show', $payable)
                ->with('error', 'Conta ja paga nao pode ser cancelada.');
        }

        $this->service->cancel($payable, auth()->user());

        Cache::increment('dashboard_finance_version');

        return redirect()
            ->route('payables.index')
            ->with('success', 'Conta cancelada com sucesso.');
    }
}
