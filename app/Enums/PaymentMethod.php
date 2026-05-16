<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case PIX = 'PIX';
    case BANK_SLIP = 'BANK_SLIP';
    case CASH = 'CASH';
    case CARD = 'CARD';
    case BANK_TRANSFER = 'BANK_TRANSFER';

    public function label(): string
    {
        return match ($this) {
            self::PIX => 'PIX',
            self::BANK_SLIP => 'Boleto',
            self::CASH => 'Dinheiro',
            self::CARD => 'Cartão',
            self::BANK_TRANSFER => 'Transferência bancária',
        };
    }
}
