<?php

namespace App\Enums;

enum PayableStatus: string
{
    case OPEN = 'OPEN';
    case PAID = 'PAID';
    case OVERDUE = 'OVERDUE';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Em aberto',
            self::PAID => 'Pago',
            self::OVERDUE => 'Vencido',
            self::CANCELLED => 'Cancelado',
        };
    }
}
