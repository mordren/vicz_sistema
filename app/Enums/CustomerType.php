<?php

namespace App\Enums;

enum CustomerType: string
{
    case INDIVIDUAL = 'INDIVIDUAL';
    case COMPANY = 'COMPANY';

    public function label(): string
    {
        return match ($this) {
            self::INDIVIDUAL => 'Pessoa fisica',
            self::COMPANY => 'Pessoa juridica',
        };
    }
}
