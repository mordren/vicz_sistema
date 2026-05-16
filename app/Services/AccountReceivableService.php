<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\ReceivableStatus;
use App\Models\AccountReceivable;
use App\Models\Attachment;
use App\Models\Customer;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountReceivableService
{
    public function syncOverdueStatuses(): void
    {
        AccountReceivable::query()
            ->where('status', ReceivableStatus::EXPECTED)
            ->whereDate('expected_date', '<', now()->toDateString())
            ->update(['status' => ReceivableStatus::OVERDUE]);
    }

    public function syncOneReceivableOverdueStatus(AccountReceivable $receivable): void
    {
        if ($receivable->status === ReceivableStatus::EXPECTED && $receivable->expected_date?->isPast()) {
            $receivable->update(['status' => ReceivableStatus::OVERDUE]);
            $receivable->refresh();
        }
    }

    public function create(array $data, User $actor): AccountReceivable
    {
        $hasReceivableCustomerId = Schema::hasColumn('account_receivables', 'customer_id');
        $hasWorkOrderCustomerId = Schema::hasColumn('work_orders', 'customer_id');
        $hasCustomersTable = Schema::hasTable('customers');

        $workOrder = null;
        if (! empty($data['work_order_id'])) {
            $workOrder = WorkOrder::query()->find($data['work_order_id']);
            $data['work_order_number'] = $workOrder?->number;

            if ($hasReceivableCustomerId && $hasWorkOrderCustomerId) {
                $data['customer_id'] = $workOrder?->customer_id;
            }

            $data['customer'] = $workOrder?->customer;
        } else {
            $data['work_order_number'] = null;

            if ($hasCustomersTable && ! empty($data['customer_id'])) {
                $customer = Customer::query()->find((int) $data['customer_id']);
                $data['customer'] = $customer?->name;
            }
        }

        if (! $hasReceivableCustomerId) {
            unset($data['customer_id']);
        }

        $data['status'] = $this->statusFromExpectedDate((string) $data['expected_date']);
        $data['created_by'] = $actor->id;
        $data['updated_by'] = $actor->id;

        $receivable = AccountReceivable::query()->create($data);

        if ($workOrder !== null) {
            $this->recalculateWorkOrderReceivableTotals($workOrder, $actor);
        }

        return $receivable;
    }

    public function update(AccountReceivable $receivable, array $data, User $actor): AccountReceivable
    {
        $hasReceivableCustomerId = Schema::hasColumn('account_receivables', 'customer_id');
        $hasWorkOrderCustomerId = Schema::hasColumn('work_orders', 'customer_id');
        $hasCustomersTable = Schema::hasTable('customers');

        $oldWorkOrderId = $receivable->work_order_id;

        $workOrder = null;
        if (! empty($data['work_order_id'])) {
            $workOrder = WorkOrder::query()->find($data['work_order_id']);
            $data['work_order_number'] = $workOrder?->number;

            if ($hasReceivableCustomerId && $hasWorkOrderCustomerId) {
                $data['customer_id'] = $workOrder?->customer_id;
            }

            $data['customer'] = $workOrder?->customer;
        } elseif (array_key_exists('work_order_id', $data) && empty($data['work_order_id'])) {
            $data['work_order_number'] = null;

            if ($hasCustomersTable && ! empty($data['customer_id'])) {
                $customer = Customer::query()->find((int) $data['customer_id']);
                $data['customer'] = $customer?->name;
            }
        } elseif ($hasCustomersTable && ! empty($data['customer_id'])) {
            $customer = Customer::query()->find((int) $data['customer_id']);
            $data['customer'] = $customer?->name;
        }

        if (! $hasReceivableCustomerId) {
            unset($data['customer_id']);
        }

        $expectedDate = (string) ($data['expected_date'] ?? $receivable->expected_date?->toDateString());
        $data['status'] = $this->statusFromExpectedDate($expectedDate);
        $data['updated_by'] = $actor->id;

        $receivable->update($data);

        if ($oldWorkOrderId !== null && $oldWorkOrderId !== $receivable->work_order_id) {
            $oldWorkOrder = WorkOrder::query()->find($oldWorkOrderId);
            if ($oldWorkOrder !== null) {
                $this->recalculateWorkOrderReceivableTotals($oldWorkOrder, $actor);
            }
        }

        if ($workOrder !== null) {
            $this->recalculateWorkOrderReceivableTotals($workOrder, $actor);
        }

        return $receivable;
    }

    public function markAsReceived(
        AccountReceivable $receivable,
        string $receivedDate,
        float $receivedAmount,
        PaymentMethod $paymentMethod,
        ?string $notes,
        ?UploadedFile $receiptFile,
        User $actor
    ): void {
        DB::transaction(function () use ($receivable, $receivedDate, $receivedAmount, $paymentMethod, $notes, $receiptFile, $actor) {
            $receivable->update([
                'status' => ReceivableStatus::RECEIVED,
                'received_date' => $receivedDate,
                'received_amount' => $receivedAmount,
                'payment_method' => $paymentMethod,
                'notes' => $notes ?? $receivable->notes,
                'updated_by' => $actor->id,
            ]);

            if ($receiptFile !== null && $actor->can('create', Attachment::class)) {
                $storedPath = $receiptFile->store('attachments/receivables/'.$receivable->id, 'public');

                $receivable->attachments()->create([
                    'file_name' => $receiptFile->getClientOriginalName(),
                    'storage_path' => $storedPath,
                    'mime_type' => $receiptFile->getMimeType(),
                    'size' => $receiptFile->getSize(),
                    'uploaded_by' => $actor->id,
                ]);
            }

            if ($receivable->workOrder !== null) {
                $this->recalculateWorkOrderReceivableTotals($receivable->workOrder, $actor);
            }
        });
    }

    public function cancel(AccountReceivable $receivable, User $actor, ?string $cancelReason = null): void
    {
        $notes = $receivable->notes;
        if ($cancelReason !== null && trim($cancelReason) !== '') {
            $prefix = $notes ? "\n\n" : '';
            $notes .= $prefix.'Cancelamento: '.trim($cancelReason);
        }

        $receivable->update([
            'status' => ReceivableStatus::CANCELLED,
            'updated_by' => $actor->id,
            'notes' => $notes,
        ]);

        if ($receivable->workOrder !== null) {
            $this->recalculateWorkOrderReceivableTotals($receivable->workOrder, $actor);
        }
    }

    public function recalculateWorkOrderReceivableTotals(WorkOrder $workOrder, ?User $actor = null): void
    {
        $receivedTotal = (float) AccountReceivable::query()
            ->where('work_order_id', $workOrder->id)
            ->where('status', ReceivableStatus::RECEIVED)
            ->sum('received_amount');

        $contracted = (float) $workOrder->contracted_amount;
        $balance = max($contracted - $receivedTotal, 0);

        $workOrder->update([
            'received_amount' => $receivedTotal,
            'receivable_balance' => $balance,
            'updated_by' => $actor?->id,
        ]);
    }

    private function statusFromExpectedDate(string $expectedDate): ReceivableStatus
    {
        return $expectedDate < now()->toDateString()
            ? ReceivableStatus::OVERDUE
            : ReceivableStatus::EXPECTED;
    }
}
