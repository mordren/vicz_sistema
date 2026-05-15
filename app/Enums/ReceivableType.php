<?php

namespace App\Enums;

enum ReceivableType: string
{
    case DOWN_PAYMENT = 'DOWN_PAYMENT';
    case INSTALLMENT = 'INSTALLMENT';
    case COMPLETION_PAYMENT = 'COMPLETION_PAYMENT';
    case FINAL_BALANCE = 'FINAL_BALANCE';
    case MAINTENANCE = 'MAINTENANCE';
    case DIRECT_SALE = 'DIRECT_SALE';

    public function label(): string
    {
        return match($this) {
            self::DOWN_PAYMENT => 'Entrada',
            self::INSTALLMENT => 'Parcela',
            self::COMPLETION_PAYMENT => 'Pagamento na entrega',
            self::FINAL_BALANCE => 'Saldo final',
            self::MAINTENANCE => 'Manutenção',
            self::DIRECT_SALE => 'Venda direta',
        };
    }
}
