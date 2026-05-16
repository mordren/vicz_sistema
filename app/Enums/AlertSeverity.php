<?php

namespace App\Enums;

enum AlertSeverity: string
{
    case INFO = 'INFO';
    case WARNING = 'WARNING';
    case CRITICAL = 'CRITICAL';

    public function label(): string
    {
        return match ($this) {
            self::INFO => 'Informação',
            self::WARNING => 'Atenção',
            self::CRITICAL => 'Crítico',
        };
    }
}
