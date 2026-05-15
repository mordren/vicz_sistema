<?php

namespace App\Enums;

enum AlertStatus: string
{
    case OPEN = 'OPEN';
    case READ = 'READ';
    case RESOLVED = 'RESOLVED';

    public function label(): string
    {
        return match($this) {
            self::OPEN => 'Aberto',
            self::READ => 'Lido',
            self::RESOLVED => 'Resolvido',
        };
    }
}
