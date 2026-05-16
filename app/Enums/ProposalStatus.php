<?php

namespace App\Enums;

enum ProposalStatus: string
{
    case SENT = 'SENT';
    case NEGOTIATING = 'NEGOTIATING';
    case APPROVED = 'APPROVED';
    case LOST = 'LOST';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::SENT => 'Enviado',
            self::NEGOTIATING => 'Em negociação',
            self::APPROVED => 'Aprovado',
            self::LOST => 'Perdido',
            self::CANCELLED => 'Cancelado',
        };
    }
}
