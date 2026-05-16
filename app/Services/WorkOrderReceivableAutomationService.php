<?php

namespace App\Services;

use App\Enums\ReceivableStatus;
use App\Enums\ReceivableType;
use App\Models\AccountReceivable;
use App\Models\User;
use App\Models\WorkOrder;

class WorkOrderReceivableAutomationService
{
    public function __construct(private readonly AccountReceivableService $receivableService) {}

    /**
     * Create expected down-payment receivable on work order creation if a down payment was specified.
     * Prevents duplicate down-payment receivables for the same work order.
     */
    public function createDownPaymentReceivableIfNeeded(WorkOrder $workOrder, User $actor): ?AccountReceivable
    {
        if ($workOrder->down_payment_amount <= 0) {
            return null;
        }

        $existingDownPayment = AccountReceivable::query()
            ->where('work_order_id', $workOrder->id)
            ->where('receivable_type', ReceivableType::DOWN_PAYMENT)
            ->first();

        if ($existingDownPayment !== null) {
            return null;
        }

        return $this->receivableService->create([
            'work_order_id' => $workOrder->id,
            'customer_id' => $workOrder->customer_id,
            'customer' => $workOrder->customer,
            'receivable_type' => ReceivableType::DOWN_PAYMENT,
            'description' => 'Entrada - OS '.$workOrder->number,
            'expected_date' => now()->toDateString(),
            'expected_amount' => $workOrder->down_payment_amount,
            'status' => ReceivableStatus::EXPECTED,
        ], $actor);
    }

    /**
     * Create expected final-balance receivable on work order delivery if there is an outstanding balance.
     * Prevents duplicate final-balance receivables for the same work order.
     */
    public function createFinalBalanceReceivableIfNeeded(WorkOrder $workOrder, string $deliveryDate, User $actor): ?AccountReceivable
    {
        if ($workOrder->receivable_balance <= 0) {
            return null;
        }

        $existingBalance = AccountReceivable::query()
            ->where('work_order_id', $workOrder->id)
            ->where('receivable_type', ReceivableType::FINAL_BALANCE)
            ->first();

        if ($existingBalance !== null) {
            return null;
        }

        return $this->receivableService->create([
            'work_order_id' => $workOrder->id,
            'customer_id' => $workOrder->customer_id,
            'customer' => $workOrder->customer,
            'receivable_type' => ReceivableType::FINAL_BALANCE,
            'description' => 'Saldo final - OS '.$workOrder->number,
            'expected_date' => $deliveryDate,
            'expected_amount' => $workOrder->receivable_balance,
            'status' => ReceivableStatus::EXPECTED,
        ], $actor);
    }

    /**
     * Attempt to auto-fulfill down-payment receivable once work order shows it's been received.
     * Used for workflows where down payment confirmation is integrated with WO status changes.
     */
    public function markDownPaymentReceivedIfNeeded(WorkOrder $workOrder, User $actor): void
    {
        $downPaymentReceivable = AccountReceivable::query()
            ->where('work_order_id', $workOrder->id)
            ->where('receivable_type', ReceivableType::DOWN_PAYMENT)
            ->where('status', '!=', ReceivableStatus::RECEIVED)
            ->first();

        if ($downPaymentReceivable === null) {
            return;
        }

        if ($workOrder->received_amount >= $downPaymentReceivable->expected_amount) {
            $this->receivableService->markAsReceived(
                $downPaymentReceivable,
                now()->toDateString(),
                $downPaymentReceivable->expected_amount,
                $workOrder->payment_method ?? \App\Enums\PaymentMethod::TRANSFER,
                'Auto-marcado como recebido via fluxo da ordem de serviço.',
                null,
                $actor
            );
        }
    }
}
