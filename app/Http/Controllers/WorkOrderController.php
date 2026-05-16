<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Enums\WorkOrderStatus;
use App\Http\Requests\StoreWorkOrderRequest;
use App\Http\Requests\UpdateWorkOrderRequest;
use App\Http\Requests\UpdateWorkOrderStatusRequest;
use App\Models\Customer;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\WorkOrderFinancialService;
use App\Services\WorkOrderReceivableAutomationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WorkOrderController extends Controller
{
    public function __construct(
        private readonly WorkOrderFinancialService $financialService,
        private readonly WorkOrderReceivableAutomationService $automationService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', WorkOrder::class);

        $hasWorkOrderCustomerId = Schema::hasColumn('work_orders', 'customer_id');
        $hasCustomersTable = Schema::hasTable('customers');

        $filters = [
            'customer_id' => (string) $request->string('customer_id'),
            'status' => (string) $request->string('status'),
            'responsible_user_id' => (string) $request->string('responsible_user_id'),
            'delivery_from' => (string) $request->string('delivery_from'),
            'delivery_to' => (string) $request->string('delivery_to'),
            'overdue_only' => (bool) $request->boolean('overdue_only'),
        ];

        $query = WorkOrder::query()
            ->with(['responsibleUser:id,name'])
            ->orderByRaw("CASE status WHEN 'IN_PRODUCTION' THEN 0 WHEN 'READY_FOR_DELIVERY' THEN 1 WHEN 'DELIVERED' THEN 2 WHEN 'CANCELLED' THEN 3 ELSE 4 END")
            ->orderBy('expected_delivery_date')
            ->orderByDesc('id');

        if ($hasWorkOrderCustomerId && $hasCustomersTable) {
            $query->with('customerRef:id,name');
        }

        if ($hasWorkOrderCustomerId && $filters['customer_id'] !== '') {
            $query->where('customer_id', (int) $filters['customer_id']);
        }

        if (($status = WorkOrderStatus::tryFrom($filters['status'])) !== null) {
            $query->where('status', $status);
        }

        if ($filters['responsible_user_id'] !== '') {
            $query->where('responsible_user_id', (int) $filters['responsible_user_id']);
        }

        if ($filters['delivery_from'] !== '') {
            $query->whereDate('expected_delivery_date', '>=', $filters['delivery_from']);
        }

        if ($filters['delivery_to'] !== '') {
            $query->whereDate('expected_delivery_date', '<=', $filters['delivery_to']);
        }

        if ($filters['overdue_only']) {
            $query->whereDate('expected_delivery_date', '<', now()->toDateString())
                ->whereNotIn('status', [WorkOrderStatus::DELIVERED, WorkOrderStatus::CANCELLED]);
        }

        $workOrders = $query->paginate(15)->withQueryString();

        $this->financialService->recalculateCollection($workOrders->getCollection(), $request->user());

        $highlightMap = $workOrders->getCollection()->mapWithKeys(function (WorkOrder $workOrder): array {
            return [$workOrder->id => [
                'is_overdue' => $this->financialService->isOverdue($workOrder),
                'is_near_delivery' => $this->financialService->isNearDelivery($workOrder),
            ]];
        });

        $summaryCards = [
            'totalOpen' => (int) WorkOrder::query()
                ->whereNotIn('status', [WorkOrderStatus::DELIVERED, WorkOrderStatus::CANCELLED])
                ->count(),
            'dueIn3Days' => (int) WorkOrder::query()
                ->whereBetween('expected_delivery_date', [now()->toDateString(), now()->addDays(3)->toDateString()])
                ->whereNotIn('status', [WorkOrderStatus::DELIVERED, WorkOrderStatus::CANCELLED])
                ->count(),
            'overdue' => (int) WorkOrder::query()
                ->whereDate('expected_delivery_date', '<', now()->toDateString())
                ->whereNotIn('status', [WorkOrderStatus::DELIVERED, WorkOrderStatus::CANCELLED])
                ->count(),
            'contractedTotal' => (float) WorkOrder::query()->sum('contracted_amount'),
            'openBalance' => (float) WorkOrder::query()
                ->whereNotIn('status', [WorkOrderStatus::CANCELLED])
                ->sum('receivable_balance'),
        ];

        $responsibleUsers = User::query()
            ->whereIn('role', [UserRole::ADMIN, UserRole::OPERATIONS])
            ->orderBy('name')
            ->get(['id', 'name']);

        $customers = ($hasWorkOrderCustomerId && $hasCustomersTable)
            ? Customer::query()->active()->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('work-orders.index', [
            'workOrders' => $workOrders,
            'statuses' => WorkOrderStatus::cases(),
            'filters' => $filters,
            'summaryCards' => $summaryCards,
            'highlightMap' => $highlightMap,
            'responsibleUsers' => $responsibleUsers,
            'customers' => $customers,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', WorkOrder::class);

        $hasWorkOrderCustomerId = Schema::hasColumn('work_orders', 'customer_id');
        $hasCustomersTable = Schema::hasTable('customers');

        $responsibleUsers = User::query()
            ->whereIn('role', [UserRole::ADMIN, UserRole::OPERATIONS])
            ->orderBy('name')
            ->get(['id', 'name']);

        $customers = ($hasWorkOrderCustomerId && $hasCustomersTable)
            ? Customer::query()->active()->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('work-orders.create', [
            'statuses' => WorkOrderStatus::cases(),
            'responsibleUsers' => $responsibleUsers,
            'customers' => $customers,
        ]);
    }

    public function store(StoreWorkOrderRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $hasWorkOrderCustomerId = Schema::hasColumn('work_orders', 'customer_id');
        $hasCustomersTable = Schema::hasTable('customers');

        $contracted = (float) $data['contracted_amount'];
        $downPayment = (float) ($data['down_payment_amount'] ?? 0);
        $estimatedCost = (float) $data['estimated_cost'];
        $customerId = (int) ($data['customer_id'] ?? 0);
        $customerName = trim((string) ($data['customer'] ?? ''));

        if ($hasCustomersTable && $customerId > 0) {
            $customerName = (string) (Customer::query()->find($customerId)?->name ?? $customerName);
        }

        $payload = [
            'number' => $this->generateWorkOrderNumber(),
            'customer' => $customerName,
            'service_type' => $data['service_type'],
            'description' => $data['description'] ?? null,
            'responsible_user_id' => $data['responsible_user_id'] ?: null,
            'opened_at' => $data['opened_at'],
            'expected_delivery_date' => $data['expected_delivery_date'],
            'recalculated_delivery_date' => $data['recalculated_delivery_date'] ?: null,
            'status' => WorkOrderStatus::QUOTE_APPROVED,
            'progress_percentage' => 0,
            'contracted_amount' => $contracted,
            'down_payment_amount' => $downPayment,
            'received_amount' => 0,
            'receivable_balance' => $contracted,
            'estimated_cost' => $estimatedCost,
            'actual_cost' => 0,
            'estimated_margin' => $contracted - $estimatedCost,
            'actual_margin' => $contracted,
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ];

        if ($hasWorkOrderCustomerId) {
            $payload['customer_id'] = $customerId > 0 ? $customerId : null;
        }

        $workOrder = WorkOrder::query()->create($payload);

        $this->financialService->recalculateFinancials($workOrder, $request->user());
        $this->automationService->createDownPaymentReceivableIfNeeded($workOrder, $request->user());

        return redirect()
            ->route('work-orders.show', $workOrder)
            ->with('success', 'Ordem de serviço criada com sucesso.');
    }

    public function show(WorkOrder $workOrder): View
    {
        $this->authorize('view', $workOrder);

        $this->financialService->recalculateFinancials($workOrder, request()->user());

        $workOrder->load([
            'responsibleUser:id,name',
            'payables:id,work_order_id,supplier,description,due_date,amount,status,paid_amount,payment_date',
            'receivables:id,work_order_id,customer,description,expected_date,expected_amount,status,received_amount,received_date',
            'attachments.uploadedBy:id,name',
        ]);

        $isOverdue = $this->financialService->isOverdue($workOrder);
        $isNearDelivery = $this->financialService->isNearDelivery($workOrder);

        return view('work-orders.show', [
            'workOrder' => $workOrder,
            'isOverdue' => $isOverdue,
            'isNearDelivery' => $isNearDelivery,
            'statuses' => WorkOrderStatus::cases(),
            'responsibleUsers' => User::query()
                ->whereIn('role', [UserRole::ADMIN, UserRole::OPERATIONS])
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function edit(WorkOrder $workOrder): View|RedirectResponse
    {
        $this->authorize('update', $workOrder);

        $hasWorkOrderCustomerId = Schema::hasColumn('work_orders', 'customer_id');
        $hasCustomersTable = Schema::hasTable('customers');

        if (in_array($workOrder->status, [WorkOrderStatus::DELIVERED, WorkOrderStatus::CANCELLED], true)) {
            return redirect()
                ->route('work-orders.show', $workOrder)
                ->with('error', 'Ordens entregues ou canceladas não podem ser editadas.');
        }

        $responsibleUsers = User::query()
            ->whereIn('role', [UserRole::ADMIN, UserRole::OPERATIONS])
            ->orderBy('name')
            ->get(['id', 'name']);

        $customers = ($hasWorkOrderCustomerId && $hasCustomersTable)
            ? Customer::query()->active()->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('work-orders.edit', [
            'workOrder' => $workOrder,
            'statuses' => WorkOrderStatus::cases(),
            'responsibleUsers' => $responsibleUsers,
            'customers' => $customers,
        ]);
    }

    public function update(UpdateWorkOrderRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        $this->authorize('update', $workOrder);

        $hasWorkOrderCustomerId = Schema::hasColumn('work_orders', 'customer_id');
        $hasCustomersTable = Schema::hasTable('customers');

        if (in_array($workOrder->status, [WorkOrderStatus::DELIVERED, WorkOrderStatus::CANCELLED], true)) {
            return redirect()
                ->route('work-orders.show', $workOrder)
                ->with('error', 'Ordens entregues ou canceladas não podem ser editadas.');
        }

        $data = $request->validated();

        $contracted = (float) $data['contracted_amount'];
        $estimatedCost = (float) $data['estimated_cost'];
        $customerId = (int) ($data['customer_id'] ?? 0);
        $customerName = trim((string) ($data['customer'] ?? ''));

        if ($hasCustomersTable && $customerId > 0) {
            $customerName = (string) (Customer::query()->find($customerId)?->name ?? $customerName);
        }

        $payload = [
            'customer' => $customerName,
            'service_type' => $data['service_type'],
            'description' => $data['description'] ?? null,
            'responsible_user_id' => $data['responsible_user_id'] ?: null,
            'opened_at' => $data['opened_at'],
            'expected_delivery_date' => $data['expected_delivery_date'],
            'recalculated_delivery_date' => $data['recalculated_delivery_date'] ?: null,
            'contracted_amount' => $contracted,
            'down_payment_amount' => (float) ($data['down_payment_amount'] ?? 0),
            'estimated_cost' => $estimatedCost,
            'notes' => $data['notes'] ?? null,
            'updated_by' => $request->user()->id,
        ];

        if ($hasWorkOrderCustomerId) {
            $payload['customer_id'] = $customerId > 0 ? $customerId : null;
        }

        $workOrder->update($payload);

        $this->financialService->recalculateFinancials($workOrder, $request->user());

        return redirect()
            ->route('work-orders.show', $workOrder)
            ->with('success', 'Ordem de serviço atualizada com sucesso.');
    }

    public function operationalUpdate(UpdateWorkOrderStatusRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        if (in_array($workOrder->status, [WorkOrderStatus::DELIVERED, WorkOrderStatus::CANCELLED], true)) {
            return redirect()
                ->route('work-orders.show', $workOrder)
                ->with('error', 'Ordens entregues ou canceladas não permitem atualização operacional.');
        }

        $this->financialService->applyOperationalUpdate($workOrder, $request->validated(), $request->user());

        return redirect()
            ->route('work-orders.show', $workOrder)
            ->with('success', 'Painel operacional atualizado com sucesso.');
    }

    public function markDelivered(UpdateWorkOrderStatusRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        if ($workOrder->status === WorkOrderStatus::CANCELLED) {
            return redirect()
                ->route('work-orders.show', $workOrder)
                ->with('error', 'Não é possível entregar uma ordem cancelada.');
        }

        $data = $request->validated();

        $this->financialService->markAsDelivered(
            $workOrder,
            (string) $data['actual_delivery_date'],
            $request->user(),
            $data['notes'] ?? null
        );

        return redirect()
            ->route('work-orders.show', $workOrder)
            ->with('success', 'Ordem marcada como entregue.');
    }

    public function cancel(UpdateWorkOrderStatusRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        if ($workOrder->status === WorkOrderStatus::DELIVERED) {
            return redirect()
                ->route('work-orders.show', $workOrder)
                ->with('error', 'Ordens já entregues não podem ser canceladas.');
        }

        $this->financialService->cancel($workOrder, $request->user(), $request->string('cancel_reason')->toString());

        return redirect()
            ->route('work-orders.show', $workOrder)
            ->with('success', 'Ordem de serviço cancelada com sucesso.');
    }

    public function destroy(WorkOrder $workOrder): RedirectResponse
    {
        $this->authorize('delete', $workOrder);

        if ($workOrder->status === WorkOrderStatus::DELIVERED) {
            return redirect()
                ->route('work-orders.show', $workOrder)
                ->with('error', 'Ordens já entregues não podem ser canceladas.');
        }

        $this->financialService->cancel($workOrder, auth()->user());

        return redirect()
            ->route('work-orders.index')
            ->with('success', 'Ordem de serviço cancelada com sucesso.');
    }

    private function generateWorkOrderNumber(): string
    {
        $year = now()->format('Y');

        $last = WorkOrder::query()
            ->where('number', 'like', 'OS-'.$year.'-%')
            ->orderByDesc('id')
            ->value('number');

        if ($last === null) {
            return 'OS-'.$year.'-0001';
        }

        $sequence = (int) Str::of($last)->afterLast('-')->toString();

        return 'OS-'.$year.'-'.str_pad((string) ($sequence + 1), 4, '0', STR_PAD_LEFT);
    }
}
