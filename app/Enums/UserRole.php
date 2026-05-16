<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'ADMIN';
    case FINANCE = 'FINANCE';
    case OPERATIONS = 'OPERATIONS';
    case SALES = 'SALES';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrador',
            self::FINANCE => 'Financeiro',
            self::OPERATIONS => 'Operações',
            self::SALES => 'Comercial',
        };
    }
}
