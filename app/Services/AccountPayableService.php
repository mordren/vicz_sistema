<?php

namespace App\Services;

use App\Enums\PayableStatus;
use App\Enums\PaymentMethod;
use App\Models\AccountPayable;
use App\Models\Attachment;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AccountPayableService
{
    public function __construct(private readonly WorkOrderFinancialService $workOrderFinancialService) {}

    public function syncOverdueStatuses(): void
    {
        AccountPayable::query()
            ->where('status', PayableStatus::OPEN)
            ->whereDate('due_date', '<', now()->toDateString())
            ->update(['status' => PayableStatus::OVERDUE]);
    }

    public function syncOnePayableOverdueStatus(AccountPayable $payable): void
    {
        if ($payable->status === PayableStatus::OPEN && $payable->due_date?->isPast()) {
            $payable->update(['status' => PayableStatus::OVERDUE]);
            $payable->refresh();
        }
    }

    /**
     * @return array{firstPayable: AccountPayable, count: int}
     */
    public function create(array $data, User $actor, ?UploadedFile $receiptFile = null): array
    {
        $installments = (int) ($data['total_installments'] ?? 1);

        $created = DB::transaction(function () use ($data, $actor, $installments, $receiptFile): Collection {
            if ($installments <= 1) {
                $payable = $this->createSinglePayable($data, $actor, 1, 1);

                if ($receiptFile !== null && $actor->can('create', Attachment::class)) {
                    $this->storeReceipt($payable, $receiptFile, $actor);
                }

                return new Collection([$payable]);
            }

            $baseDate = Carbon::parse((string) $data['due_date']);
            $installmentAmounts = $this->splitAmount((float) $data['amount'], $installments);
            $created = new Collection();

            for ($number = 1; $number <= $installments; $number++) {
                $installmentData = $data;
                $installmentData['amount'] = $installmentAmounts[$number - 1];
                $installmentData['due_date'] = $baseDate->copy()->addMonthsNoOverflow($number - 1)->toDateString();

                $created->push($this->createSinglePayable($installmentData, $actor, $number, $installments));
            }

            if ($receiptFile !== null && $actor->can('create', Attachment::class) && $created->isNotEmpty()) {
                /** @var AccountPayable $first */
                $first = $created->first();
                $this->storeReceipt($first, $receiptFile, $actor);
            }

            return $created;
        });

        $workOrderIds = $created
            ->pluck('work_order_id')
            ->filter()
            ->unique()
            ->all();

        WorkOrder::query()
            ->whereIn('id', $workOrderIds)
            ->get()
            ->each(fn (WorkOrder $workOrder) => $this->workOrderFinancialService->recalculateFinancials($workOrder, $actor));

        /** @var AccountPayable $first */
        $first = $created->first();

        return [
            'firstPayable' => $first,
            'count' => $created->count(),
        ];
    }

    public function update(AccountPayable $payable, array $data, User $actor): AccountPayable
    {
        $oldWorkOrderId = $payable->work_order_id;

        $data['status'] = $this->statusFromDueDate((string) $data['due_date']);
        $data['updated_by'] = $actor->id;

        if ($data['status'] === PayableStatus::OPEN && $payable->status === PayableStatus::OVERDUE) {
            $data['status'] = $this->statusFromDueDate((string) $data['due_date']);
        }

        $payable->update($data);

        if ($oldWorkOrderId !== null && $oldWorkOrderId !== $payable->work_order_id) {
            $oldWorkOrder = WorkOrder::query()->find($oldWorkOrderId);
            if ($oldWorkOrder !== null) {
                $this->workOrderFinancialService->recalculateFinancials($oldWorkOrder, $actor);
            }
        }

        if ($payable->workOrder !== null) {
            $this->workOrderFinancialService->recalculateFinancials($payable->workOrder, $actor);
        }

        return $payable;
    }

    public function markAsPaid(
        AccountPayable $payable,
        string $paymentDate,
        float $paidAmount,
        PaymentMethod $paymentMethod,
        ?string $notes,
        ?UploadedFile $receiptFile,
        User $actor
    ): void {
        DB::transaction(function () use ($payable, $paymentDate, $paidAmount, $paymentMethod, $notes, $receiptFile, $actor): void {
            $payable->update([
                'status' => PayableStatus::PAID,
                'payment_date' => $paymentDate,
                'paid_amount' => $paidAmount,
                'payment_method' => $paymentMethod,
                'notes' => $notes ?? $payable->notes,
                'updated_by' => $actor->id,
            ]);

            if ($receiptFile !== null && $actor->can('create', Attachment::class)) {
                $this->storeReceipt($payable, $receiptFile, $actor);
            }

            if ($payable->workOrder !== null) {
                $this->workOrderFinancialService->recalculateFinancials($payable->workOrder, $actor);
            }
        });
    }

    public function cancel(AccountPayable $payable, User $actor, ?string $cancelReason = null): void
    {
        $notes = $payable->notes;
        if ($cancelReason !== null && trim($cancelReason) !== '') {
            $prefix = $notes ? "\n\n" : '';
            $notes .= $prefix.'Cancelamento: '.trim($cancelReason);
        }

        $payable->update([
            'status' => PayableStatus::CANCELLED,
            'updated_by' => $actor->id,
            'notes' => $notes,
        ]);

        if ($payable->workOrder !== null) {
            $this->workOrderFinancialService->recalculateFinancials($payable->workOrder, $actor);
        }
    }

    public function delete(AccountPayable $payable, User $actor): void
    {
        $workOrder = $payable->workOrder;

        DB::transaction(function () use ($payable, $workOrder, $actor): void {
            $payable->delete();

            if ($workOrder !== null) {
                $this->workOrderFinancialService->recalculateFinancials($workOrder, $actor);
            }
        });
    }

    private function createSinglePayable(array $data, User $actor, int $installmentNumber, int $totalInstallments): AccountPayable
    {
        $payload = $data;
        $payload['status'] = $this->statusFromDueDate((string) $payload['due_date']);
        $payload['installment_number'] = $installmentNumber;
        $payload['total_installments'] = $totalInstallments;
        $payload['created_by'] = $actor->id;
        $payload['updated_by'] = $actor->id;

        return AccountPayable::query()->create($payload);
    }

    /**
     * @return array<int, float>
     */
    private function splitAmount(float $totalAmount, int $installments): array
    {
        $totalCents = (int) round($totalAmount * 100);
        $base = intdiv($totalCents, $installments);
        $remainder = $totalCents % $installments;

        $result = [];

        for ($i = 0; $i < $installments; $i++) {
            $cents = $base + ($i < $remainder ? 1 : 0);
            $result[] = $cents / 100;
        }

        return $result;
    }

    private function storeReceipt(AccountPayable $payable, UploadedFile $receiptFile, User $actor): void
    {
        $storedPath = $receiptFile->store('attachments/payables/'.$payable->id, 'public');

        $payable->attachments()->create([
            'file_name' => $receiptFile->getClientOriginalName(),
            'storage_path' => $storedPath,
            'mime_type' => $receiptFile->getMimeType(),
            'size' => $receiptFile->getSize(),
            'uploaded_by' => $actor->id,
        ]);
    }

    private function statusFromDueDate(string $dueDate): PayableStatus
    {
        return $dueDate < now()->toDateString()
            ? PayableStatus::OVERDUE
            : PayableStatus::OPEN;
    }
}
