<?php

namespace App\Enums;

enum AlertType: string
{
    case BILL_DUE_TODAY = 'BILL_DUE_TODAY';
    case BILL_DUE_TOMORROW = 'BILL_DUE_TOMORROW';
    case BILL_OVERDUE = 'BILL_OVERDUE';
    case RECEIVABLE_EXPECTED_TODAY = 'RECEIVABLE_EXPECTED_TODAY';
    case RECEIVABLE_OVERDUE = 'RECEIVABLE_OVERDUE';
    case WORK_ORDER_DUE_WITHIN_3_DAYS = 'WORK_ORDER_DUE_WITHIN_3_DAYS';
    case WORK_ORDER_OVERDUE = 'WORK_ORDER_OVERDUE';
    case WORK_ORDER_WITHOUT_RESPONSIBLE = 'WORK_ORDER_WITHOUT_RESPONSIBLE';
    case WORK_ORDER_WITHOUT_DELIVERY_DATE = 'WORK_ORDER_WITHOUT_DELIVERY_DATE';
    case PURCHASE_WITHOUT_LINKED_WORK_ORDER = 'PURCHASE_WITHOUT_LINKED_WORK_ORDER';
    case CUSTOMER_WITH_PENDING_BALANCE = 'CUSTOMER_WITH_PENDING_BALANCE';
    case PROPOSAL_WITHOUT_FOLLOW_UP = 'PROPOSAL_WITHOUT_FOLLOW_UP';

    public function label(): string
    {
        return match ($this) {
            self::BILL_DUE_TODAY => 'Conta a pagar vence hoje',
            self::BILL_DUE_TOMORROW => 'Conta a pagar vence amanha',
            self::BILL_OVERDUE => 'Conta a pagar vencida',
            self::RECEIVABLE_EXPECTED_TODAY => 'Recebivel previsto hoje',
            self::RECEIVABLE_OVERDUE => 'Recebivel em atraso',
            self::WORK_ORDER_DUE_WITHIN_3_DAYS => 'OS com entrega em ate 3 dias',
            self::WORK_ORDER_OVERDUE => 'OS em atraso',
            self::WORK_ORDER_WITHOUT_RESPONSIBLE => 'OS sem responsavel',
            self::WORK_ORDER_WITHOUT_DELIVERY_DATE => 'OS sem data de entrega',
            self::PURCHASE_WITHOUT_LINKED_WORK_ORDER => 'Compra sem OS vinculada',
            self::CUSTOMER_WITH_PENDING_BALANCE => 'Cliente com saldo pendente',
            self::PROPOSAL_WITHOUT_FOLLOW_UP => 'Proposta sem follow-up',
        };
    }
}
