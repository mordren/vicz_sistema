<?php

namespace App\Services;

use App\Enums\ProposalStatus;
use App\Enums\ReceivableStatus;
use App\Enums\ReceivableType;
use App\Enums\WorkOrderStatus;
use App\Models\AccountReceivable;
use App\Models\Proposal;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ProposalConversionService
{
    public function recalculateWeightedAmount(Proposal $proposal, ?User $actor = null): Proposal
    {
        $amount = (float) $proposal->amount;
        $probability = (int) $proposal->close_probability;

        $weightedAmount = round($amount * ($probability / 100), 2);

        $proposal->update([
            'weighted_amount' => $weightedAmount,
            'updated_by' => $actor?->id,
        ]);

        return $proposal->refresh();
    }

    public function approve(Proposal $proposal, User $actor, ?string $notes = null): Proposal
    {
        $proposal->update([
            'status' => ProposalStatus::APPROVED,
            'close_probability' => 100,
            'weighted_amount' => (float) $proposal->amount,
            'notes' => $notes ?? $proposal->notes,
            'updated_by' => $actor->id,
        ]);

        return $proposal->refresh();
    }

    public function markAsLost(Proposal $proposal, User $actor, string $lossReason, ?string $notes = null): Proposal
    {
        $proposal->update([
            'status' => ProposalStatus::LOST,
            'loss_reason' => trim($lossReason),
            'close_probability' => 0,
            'weighted_amount' => 0,
            'notes' => $notes ?? $proposal->notes,
            'updated_by' => $actor->id,
        ]);

        return $proposal->refresh();
    }

    public function cancel(Proposal $proposal, User $actor, ?string $notes = null): Proposal
    {
        $proposal->update([
            'status' => ProposalStatus::CANCELLED,
            'weighted_amount' => 0,
            'notes' => $notes ?? $proposal->notes,
            'updated_by' => $actor->id,
        ]);

        return $proposal->refresh();
    }

    public function convertToWorkOrder(Proposal $proposal, User $actor, bool $createDownPaymentReceivable = false, float $downPaymentAmount = 0): WorkOrder
    {
        if ($proposal->status !== ProposalStatus::APPROVED) {
            throw new \DomainException('Somente propostas aprovadas podem ser convertidas em ordem de serviço.');
        }

        $hasProposalId = Schema::hasColumn('work_orders', 'proposal_id');

        if ($hasProposalId) {
            $existing = WorkOrder::query()->where('proposal_id', $proposal->id)->first();
            if ($existing !== null) {
                return $existing;
            }
        }

        return DB::transaction(function () use ($proposal, $actor, $createDownPaymentReceivable, $downPaymentAmount): WorkOrder {
            $payload = [
                'number' => $this->generateWorkOrderNumber(),
                'customer' => $proposal->customer,
                'service_type' => $proposal->product_or_service_type,
                'description' => $proposal->description,
                'responsible_user_id' => $proposal->sales_owner_id,
                'opened_at' => now()->toDateString(),
                'expected_delivery_date' => ($proposal->expected_close_date ?? now()->addDays(30))->toDateString(),
                'status' => WorkOrderStatus::QUOTE_APPROVED,
                'progress_percentage' => 0,
                'contracted_amount' => (float) $proposal->amount,
                'down_payment_amount' => $createDownPaymentReceivable ? $downPaymentAmount : 0,
                'received_amount' => 0,
                'receivable_balance' => (float) $proposal->amount,
                'estimated_cost' => 0,
                'actual_cost' => 0,
                'estimated_margin' => (float) $proposal->amount,
                'actual_margin' => (float) $proposal->amount,
                'notes' => $proposal->notes,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ];

            if (Schema::hasColumn('work_orders', 'proposal_id')) {
                $payload['proposal_id'] = $proposal->id;
            }

            $workOrder = WorkOrder::query()->create($payload);

            if ($createDownPaymentReceivable && $downPaymentAmount > 0) {
                AccountReceivable::query()->create([
                    'customer' => $proposal->customer,
                    'work_order_id' => $workOrder->id,
                    'work_order_number' => $workOrder->number,
                    'description' => 'Entrada da proposta '.$proposal->number,
                    'receivable_type' => ReceivableType::DOWN_PAYMENT,
                    'expected_date' => now()->toDateString(),
                    'expected_amount' => $downPaymentAmount,
                    'status' => ReceivableStatus::EXPECTED,
                    'created_by' => $actor->id,
                    'updated_by' => $actor->id,
                ]);
            }

            return $workOrder;
        });
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
