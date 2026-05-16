<?php

namespace App\Enums;

enum WorkOrderStatus: string
{
    case QUOTE_APPROVED = 'QUOTE_APPROVED';
    case WAITING_DOWN_PAYMENT = 'WAITING_DOWN_PAYMENT';
    case WAITING_MATERIAL_PURCHASE = 'WAITING_MATERIAL_PURCHASE';
    case IN_PRODUCTION = 'IN_PRODUCTION';
    case WELDING = 'WELDING';
    case PAINTING = 'PAINTING';
    case ASSEMBLY = 'ASSEMBLY';
    case WAITING_CUSTOMER = 'WAITING_CUSTOMER';
    case READY_FOR_DELIVERY = 'READY_FOR_DELIVERY';
    case DELIVERED = 'DELIVERED';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::QUOTE_APPROVED => 'Orçamento aprovado',
            self::WAITING_DOWN_PAYMENT => 'Aguardando entrada',
            self::WAITING_MATERIAL_PURCHASE => 'Aguardando compra de material',
            self::IN_PRODUCTION => 'Em produção',
            self::WELDING => 'Soldagem',
            self::PAINTING => 'Pintura',
            self::ASSEMBLY => 'Montagem',
            self::WAITING_CUSTOMER => 'Aguardando cliente',
            self::READY_FOR_DELIVERY => 'Pronto para entrega',
            self::DELIVERED => 'Entregue',
            self::CANCELLED => 'Cancelado',
        };
    }

    public function isActive(): bool
    {
        return ! in_array($this, [self::DELIVERED, self::CANCELLED]);
    }
}
