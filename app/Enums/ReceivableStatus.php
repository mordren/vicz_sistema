<?php

namespace App\Enums;

enum ReceivableStatus: string
{
    case EXPECTED = 'EXPECTED';
    case RECEIVED = 'RECEIVED';
    case OVERDUE = 'OVERDUE';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::EXPECTED => 'Previsto',
            self::RECEIVED => 'Recebido',
            self::OVERDUE => 'Em atraso',
            self::CANCELLED => 'Cancelado',
        };
    }
}
