<?php

namespace App\Support;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Enums\PayableStatus;
use App\Enums\ProposalStatus;
use App\Enums\ReceivableStatus;
use App\Enums\WorkOrderStatus;

/**
 * Maps domain enum values to design-system badge variants and labels.
 * Used in Blade views via {{ BadgeHelper::variant($enum) }}.
 */
class BadgeHelper
{
    /**
     * Returns ['variant' => string, 'label' => string]
     * for any supported backed enum.
     */
    public static function resolve(object $enum): array
    {
        return match(true) {
            $enum instanceof PayableStatus     => self::payable($enum),
            $enum instanceof ReceivableStatus  => self::receivable($enum),
            $enum instanceof WorkOrderStatus   => self::workOrder($enum),
            $enum instanceof ProposalStatus    => self::proposal($enum),
            $enum instanceof AlertSeverity     => self::alertSeverity($enum),
            $enum instanceof AlertStatus       => self::alertStatus($enum),
            default                            => ['variant' => 'neutral', 'label' => $enum->value],
        };
    }

    public static function variant(object $enum): string
    {
        return self::resolve($enum)['variant'];
    }

    public static function label(object $enum): string
    {
        return self::resolve($enum)['label'];
    }

    // ── Payable ────────────────────────────────────────────────
    private static function payable(PayableStatus $s): array
    {
        return match($s) {
            PayableStatus::OPEN      => ['variant' => 'warning', 'label' => 'Em aberto'],
            PayableStatus::PAID      => ['variant' => 'success', 'label' => 'Pago'],
            PayableStatus::OVERDUE   => ['variant' => 'danger',  'label' => 'Vencido'],
            PayableStatus::CANCELLED => ['variant' => 'neutral', 'label' => 'Cancelado'],
        };
    }

    // ── Receivable ─────────────────────────────────────────────
    private static function receivable(ReceivableStatus $s): array
    {
        return match($s) {
            ReceivableStatus::EXPECTED  => ['variant' => 'info',    'label' => 'A receber'],
            ReceivableStatus::RECEIVED  => ['variant' => 'success', 'label' => 'Recebido'],
            ReceivableStatus::OVERDUE   => ['variant' => 'danger',  'label' => 'Em atraso'],
            ReceivableStatus::CANCELLED => ['variant' => 'neutral', 'label' => 'Cancelado'],
        };
    }

    // ── Work Order ─────────────────────────────────────────────
    private static function workOrder(WorkOrderStatus $s): array
    {
        return match($s) {
            WorkOrderStatus::QUOTE_APPROVED          => ['variant' => 'info',    'label' => 'Aprovada'],
            WorkOrderStatus::WAITING_DOWN_PAYMENT    => ['variant' => 'warning', 'label' => 'Ag. Entrada'],
            WorkOrderStatus::WAITING_MATERIAL_PURCHASE => ['variant' => 'warning', 'label' => 'Ag. Material'],
            WorkOrderStatus::IN_PRODUCTION           => ['variant' => 'info',    'label' => 'Em produção'],
            WorkOrderStatus::WELDING                 => ['variant' => 'info',    'label' => 'Soldagem'],
            WorkOrderStatus::PAINTING                => ['variant' => 'info',    'label' => 'Pintura'],
            WorkOrderStatus::ASSEMBLY                => ['variant' => 'info',    'label' => 'Montagem'],
            WorkOrderStatus::WAITING_CUSTOMER        => ['variant' => 'warning', 'label' => 'Ag. Cliente'],
            WorkOrderStatus::READY_FOR_DELIVERY      => ['variant' => 'primary', 'label' => 'Pronto entrega'],
            WorkOrderStatus::DELIVERED               => ['variant' => 'success', 'label' => 'Entregue'],
            WorkOrderStatus::CANCELLED               => ['variant' => 'neutral', 'label' => 'Cancelada'],
        };
    }

    // ── Proposal ───────────────────────────────────────────────
    private static function proposal(ProposalStatus $s): array
    {
        return match($s) {
            ProposalStatus::SENT        => ['variant' => 'info',    'label' => 'Enviada'],
            ProposalStatus::NEGOTIATING => ['variant' => 'warning', 'label' => 'Negociando'],
            ProposalStatus::APPROVED    => ['variant' => 'success', 'label' => 'Aprovada'],
            ProposalStatus::LOST        => ['variant' => 'danger',  'label' => 'Perdida'],
            ProposalStatus::CANCELLED   => ['variant' => 'neutral', 'label' => 'Cancelada'],
        };
    }

    // ── Alert Severity ─────────────────────────────────────────
    private static function alertSeverity(AlertSeverity $s): array
    {
        return match($s) {
            AlertSeverity::INFO     => ['variant' => 'info',    'label' => 'Info'],
            AlertSeverity::WARNING  => ['variant' => 'warning', 'label' => 'Atenção'],
            AlertSeverity::CRITICAL => ['variant' => 'danger',  'label' => 'Crítico'],
        };
    }

    // ── Alert Status ───────────────────────────────────────────
    private static function alertStatus(AlertStatus $s): array
    {
        return match($s) {
            AlertStatus::OPEN     => ['variant' => 'warning', 'label' => 'Aberto'],
            AlertStatus::READ     => ['variant' => 'neutral', 'label' => 'Lido'],
            AlertStatus::RESOLVED => ['variant' => 'success', 'label' => 'Resolvido'],
        };
    }
}
