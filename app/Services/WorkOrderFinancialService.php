<?php

namespace App\Services;

use App\Enums\PayableStatus;
use App\Enums\ReceivableStatus;
use App\Enums\WorkOrderStatus;
use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Collection;

class WorkOrderFinancialService
{
    public function __construct(private readonly WorkOrderReceivableAutomationService $automationService) {}

    public function recalculateFinancials(WorkOrder $workOrder, ?User $actor = null): WorkOrder
    {
        $actualCost = (float) AccountPayable::query()
            ->where('work_order_id', $workOrder->id)
            ->where('status', PayableStatus::PAID)
            ->sum('paid_amount');

        $receivedAmount = (float) AccountReceivable::query()
            ->where('work_order_id', $workOrder->id)
            ->where('status', ReceivableStatus::RECEIVED)
            ->sum('received_amount');

        $contractedAmount = (float) $workOrder->contracted_amount;
        $estimatedCost = (float) $workOrder->estimated_cost;

        $workOrder->update([
            'actual_cost' => $actualCost,
            'received_amount' => $receivedAmount,
            'receivable_balance' => $contractedAmount - $receivedAmount,
            'estimated_margin' => $contractedAmount - $estimatedCost,
            'actual_margin' => $contractedAmount - $actualCost,
            'updated_by' => $actor?->id,
        ]);

        return $workOrder->refresh();
    }

    public function recalculateCollection(Collection $workOrders, ?User $actor = null): void
    {
        $workOrders->each(fn (WorkOrder $workOrder) => $this->recalculateFinancials($workOrder, $actor));
    }

    public function isOverdue(WorkOrder $workOrder): bool
    {
        if (in_array($workOrder->status, [WorkOrderStatus::DELIVERED, WorkOrderStatus::CANCELLED], true)) {
            return false;
        }

        $deadline = $workOrder->recalculated_delivery_date ?? $workOrder->expected_delivery_date;

        return $deadline !== null && $deadline->lt(now()->startOfDay());
    }

    public function isNearDelivery(WorkOrder $workOrder): bool
    {
        if (in_array($workOrder->status, [WorkOrderStatus::DELIVERED, WorkOrderStatus::CANCELLED], true)) {
            return false;
        }

        $deadline = $workOrder->recalculated_delivery_date ?? $workOrder->expected_delivery_date;
        if ($deadline === null) {
            return false;
        }

        $today = now()->startOfDay();
        $inThreeDays = now()->addDays(3)->endOfDay();

        return $deadline->between($today, $inThreeDays);
    }

    public function applyOperationalUpdate(WorkOrder $workOrder, array $data, User $actor): WorkOrder
    {
        $workOrder->update([
            'status' => $data['status'],
            'progress_percentage' => (int) $data['progress_percentage'],
            'responsible_user_id' => $data['responsible_user_id'] ?? $workOrder->responsible_user_id,
            'recalculated_delivery_date' => $data['recalculated_delivery_date'] ?? $workOrder->recalculated_delivery_date,
            'notes' => $data['notes'] ?? $workOrder->notes,
            'updated_by' => $actor->id,
        ]);

        return $workOrder->refresh();
    }

    public function markAsDelivered(WorkOrder $workOrder, string $actualDeliveryDate, User $actor, ?string $notes = null): WorkOrder
    {
        $workOrder->update([
            'status' => WorkOrderStatus::DELIVERED,
            'actual_delivery_date' => $actualDeliveryDate,
            'progress_percentage' => 100,
            'notes' => $notes ?? $workOrder->notes,
            'updated_by' => $actor->id,
        ]);

        $workOrder = $workOrder->refresh();

        $this->automationService->createFinalBalanceReceivableIfNeeded($workOrder, $actualDeliveryDate, $actor);

        return $workOrder;
    }

    public function cancel(WorkOrder $workOrder, User $actor, ?string $reason = null): WorkOrder
    {
        $notes = $workOrder->notes;
        if ($reason !== null && trim($reason) !== '') {
            $prefix = $notes ? "\n\n" : '';
            $notes .= $prefix.'Cancelamento: '.trim($reason);
        }

        $workOrder->update([
            'status' => WorkOrderStatus::CANCELLED,
            'updated_by' => $actor->id,
            'notes' => $notes,
        ]);

        return $workOrder->refresh();
    }
}
