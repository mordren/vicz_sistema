<?php

namespace App\Services;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Enums\AlertType;
use App\Enums\PayableStatus;
use App\Enums\ProposalStatus;
use App\Enums\ReceivableStatus;
use App\Enums\WorkOrderStatus;
use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Models\Alert;
use App\Models\Proposal;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Collection;

class AlertGenerationService
{
    public function __construct(private readonly AlertNotificationDispatcher $notificationDispatcher) {}

    /**
     * @return array{created:int,updated:int,resolved:int,activeKeys:array<string, true>}
     */
    public function generate(): array
    {
        $summary = [
            'created' => 0,
            'updated' => 0,
            'resolved' => 0,
            'activeKeys' => [],
        ];

        $today = now()->startOfDay();
        $tomorrow = $today->copy()->addDay();
        $in3Days = $today->copy()->addDays(3);
        $followUpThresholdDays = 7;

        $register = function (AlertType $type, string $title, string $message, AlertSeverity $severity, ?string $entityType, ?int $entityId) use (&$summary): void {
            $result = $this->upsertOpenAlert($type, $title, $message, $severity, $entityType, $entityId);
            $summary['created'] += $result['created'];
            $summary['updated'] += $result['updated'];
            $summary['activeKeys'][$result['key']] = true;
        };

        AccountPayable::query()
            ->where('status', PayableStatus::OPEN->value)
            ->whereDate('due_date', $today->toDateString())
            ->get(['id', 'supplier', 'description', 'due_date', 'amount'])
            ->each(function (AccountPayable $payable) use ($register): void {
                $register(
                    AlertType::BILL_DUE_TODAY,
                    'Conta vence hoje',
                    'Conta de '.$payable->supplier.' vence hoje no valor de R$ '.number_format((float) $payable->amount, 2, ',', '.').'.',
                    AlertSeverity::WARNING,
                    AccountPayable::class,
                    $payable->id
                );
            });

        AccountPayable::query()
            ->where('status', PayableStatus::OPEN->value)
            ->whereDate('due_date', $tomorrow->toDateString())
            ->get(['id', 'supplier', 'amount'])
            ->each(function (AccountPayable $payable) use ($register): void {
                $register(
                    AlertType::BILL_DUE_TOMORROW,
                    'Conta vence amanha',
                    'Conta de '.$payable->supplier.' vence amanha no valor de R$ '.number_format((float) $payable->amount, 2, ',', '.').'.',
                    AlertSeverity::INFO,
                    AccountPayable::class,
                    $payable->id
                );
            });

        AccountPayable::query()
            ->whereIn('status', [PayableStatus::OPEN->value, PayableStatus::OVERDUE->value])
            ->whereDate('due_date', '<', $today->toDateString())
            ->get(['id', 'supplier', 'due_date', 'amount'])
            ->each(function (AccountPayable $payable) use ($register): void {
                $register(
                    AlertType::BILL_OVERDUE,
                    'Conta vencida',
                    'Conta de '.$payable->supplier.' esta vencida desde '.optional($payable->due_date)->format('d/m/Y').'.',
                    AlertSeverity::CRITICAL,
                    AccountPayable::class,
                    $payable->id
                );
            });

        AccountPayable::query()
            ->whereNull('work_order_id')
            ->whereIn('status', [PayableStatus::OPEN->value, PayableStatus::OVERDUE->value])
            ->get(['id', 'supplier', 'description'])
            ->each(function (AccountPayable $payable) use ($register): void {
                $register(
                    AlertType::PURCHASE_WITHOUT_LINKED_WORK_ORDER,
                    'Compra sem OS vinculada',
                    'Lancamento de compra sem OS vinculada: '.$payable->supplier.' - '.$payable->description.'.',
                    AlertSeverity::WARNING,
                    AccountPayable::class,
                    $payable->id
                );
            });

        AccountReceivable::query()
            ->where('status', ReceivableStatus::EXPECTED->value)
            ->whereDate('expected_date', $today->toDateString())
            ->get(['id', 'customer', 'expected_amount'])
            ->each(function (AccountReceivable $receivable) use ($register): void {
                $register(
                    AlertType::RECEIVABLE_EXPECTED_TODAY,
                    'Recebivel previsto hoje',
                    'Recebivel de '.$receivable->customer.' previsto para hoje no valor de R$ '.number_format((float) $receivable->expected_amount, 2, ',', '.').'.',
                    AlertSeverity::INFO,
                    AccountReceivable::class,
                    $receivable->id
                );
            });

        AccountReceivable::query()
            ->whereIn('status', [ReceivableStatus::EXPECTED->value, ReceivableStatus::OVERDUE->value])
            ->whereDate('expected_date', '<', $today->toDateString())
            ->get(['id', 'customer', 'expected_date', 'expected_amount'])
            ->each(function (AccountReceivable $receivable) use ($register): void {
                $register(
                    AlertType::RECEIVABLE_OVERDUE,
                    'Recebivel em atraso',
                    'Recebivel de '.$receivable->customer.' em atraso desde '.optional($receivable->expected_date)->format('d/m/Y').'.',
                    AlertSeverity::CRITICAL,
                    AccountReceivable::class,
                    $receivable->id
                );
            });

        WorkOrder::query()
            ->whereNotIn('status', [WorkOrderStatus::DELIVERED->value, WorkOrderStatus::CANCELLED->value])
            ->where(function ($query) use ($today, $in3Days): void {
                $query->whereBetween('expected_delivery_date', [$today->toDateString(), $in3Days->toDateString()])
                    ->orWhereBetween('recalculated_delivery_date', [$today->toDateString(), $in3Days->toDateString()]);
            })
            ->get(['id', 'number', 'customer'])
            ->each(function (WorkOrder $workOrder) use ($register): void {
                $register(
                    AlertType::WORK_ORDER_DUE_WITHIN_3_DAYS,
                    'OS proxima da entrega',
                    'OS '.$workOrder->number.' de '.$workOrder->customer.' vence em ate 3 dias.',
                    AlertSeverity::WARNING,
                    WorkOrder::class,
                    $workOrder->id
                );
            });

        WorkOrder::query()
            ->whereNotIn('status', [WorkOrderStatus::DELIVERED->value, WorkOrderStatus::CANCELLED->value])
            ->where(function ($query) use ($today): void {
                $query->whereDate('expected_delivery_date', '<', $today->toDateString())
                    ->orWhereDate('recalculated_delivery_date', '<', $today->toDateString());
            })
            ->get(['id', 'number', 'customer'])
            ->each(function (WorkOrder $workOrder) use ($register): void {
                $register(
                    AlertType::WORK_ORDER_OVERDUE,
                    'OS em atraso',
                    'OS '.$workOrder->number.' de '.$workOrder->customer.' esta em atraso.',
                    AlertSeverity::CRITICAL,
                    WorkOrder::class,
                    $workOrder->id
                );
            });

        WorkOrder::query()
            ->whereNull('responsible_user_id')
            ->whereNotIn('status', [WorkOrderStatus::DELIVERED->value, WorkOrderStatus::CANCELLED->value])
            ->get(['id', 'number', 'customer'])
            ->each(function (WorkOrder $workOrder) use ($register): void {
                $register(
                    AlertType::WORK_ORDER_WITHOUT_RESPONSIBLE,
                    'OS sem responsavel',
                    'OS '.$workOrder->number.' de '.$workOrder->customer.' nao possui responsavel atribuido.',
                    AlertSeverity::WARNING,
                    WorkOrder::class,
                    $workOrder->id
                );
            });

        WorkOrder::query()
            ->whereNull('expected_delivery_date')
            ->whereNull('recalculated_delivery_date')
            ->whereNotIn('status', [WorkOrderStatus::DELIVERED->value, WorkOrderStatus::CANCELLED->value])
            ->get(['id', 'number', 'customer'])
            ->each(function (WorkOrder $workOrder) use ($register): void {
                $register(
                    AlertType::WORK_ORDER_WITHOUT_DELIVERY_DATE,
                    'OS sem data de entrega',
                    'OS '.$workOrder->number.' de '.$workOrder->customer.' nao possui data de entrega definida.',
                    AlertSeverity::CRITICAL,
                    WorkOrder::class,
                    $workOrder->id
                );
            });

        AccountReceivable::query()
            ->whereIn('status', [ReceivableStatus::EXPECTED->value, ReceivableStatus::OVERDUE->value])
            ->selectRaw('customer, SUM(expected_amount) as pending_total, MAX(id) as sample_id')
            ->groupBy('customer')
            ->havingRaw('SUM(expected_amount) > 0')
            ->get()
            ->each(function ($row) use ($register): void {
                $register(
                    AlertType::CUSTOMER_WITH_PENDING_BALANCE,
                    'Cliente com saldo pendente',
                    'Cliente '.$row->customer.' possui saldo pendente de R$ '.number_format((float) $row->pending_total, 2, ',', '.').'.',
                    AlertSeverity::WARNING,
                    AccountReceivable::class,
                    (int) $row->sample_id
                );
            });

        Proposal::query()
            ->whereIn('status', [ProposalStatus::SENT->value, ProposalStatus::NEGOTIATING->value])
            ->where('updated_at', '<', now()->subDays($followUpThresholdDays))
            ->get(['id', 'number', 'customer', 'updated_at'])
            ->each(function (Proposal $proposal) use ($register, $followUpThresholdDays): void {
                $register(
                    AlertType::PROPOSAL_WITHOUT_FOLLOW_UP,
                    'Proposta sem follow-up',
                    'Proposta '.$proposal->number.' sem movimentacao ha mais de '.$followUpThresholdDays.' dias.',
                    AlertSeverity::WARNING,
                    Proposal::class,
                    $proposal->id
                );
            });

        $summary['resolved'] = $this->resolveStaleAlerts($summary['activeKeys']);

        return $summary;
    }

    /**
     * @return array{created:int,updated:int,key:string}
     */
    private function upsertOpenAlert(
        AlertType $type,
        string $title,
        string $message,
        AlertSeverity $severity,
        ?string $entityType,
        ?int $entityId,
    ): array {
        $existing = Alert::query()
            ->where('type', $type->value)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->whereIn('status', [AlertStatus::OPEN->value, AlertStatus::READ->value])
            ->first();

        $key = $this->buildKey($type->value, $entityType, $entityId);

        if ($existing !== null) {
            $existing->update([
                'severity' => $severity,
                'title' => $title,
                'message' => $message,
                'status' => AlertStatus::OPEN,
                'resolved_at' => null,
            ]);

            return ['created' => 0, 'updated' => 1, 'key' => $key];
        }

        $alert = Alert::query()->create([
            'type' => $type,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'status' => AlertStatus::OPEN,
        ]);

        $this->notificationDispatcher->dispatchForNewAlert($alert);

        return ['created' => 1, 'updated' => 0, 'key' => $key];
    }

    /**
     * @param  array<string, true>  $activeKeys
     */
    private function resolveStaleAlerts(array $activeKeys): int
    {
        $openAlerts = Alert::query()
            ->whereIn('status', [AlertStatus::OPEN->value, AlertStatus::READ->value])
            ->get(['id', 'type', 'entity_type', 'entity_id']);

        $resolved = 0;

        /** @var Collection<int, Alert> $openAlerts */
        foreach ($openAlerts as $alert) {
            $type = is_string($alert->type) ? $alert->type : (string) $alert->type;
            $key = $this->buildKey($type, $alert->entity_type, $alert->entity_id);

            if (isset($activeKeys[$key])) {
                continue;
            }

            $alert->resolve();
            $resolved++;
        }

        return $resolved;
    }

    private function buildKey(string $type, ?string $entityType, ?int $entityId): string
    {
        return $type.'|'.($entityType ?? '-').'|'.((string) ($entityId ?? 0));
    }
}
