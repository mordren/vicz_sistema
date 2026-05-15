<?php

namespace App\Enums;

enum PayableCategory: string
{
    case RAW_MATERIAL = 'RAW_MATERIAL';
    case METAL_SHEET = 'METAL_SHEET';
    case PROFILE = 'PROFILE';
    case PAINTING = 'PAINTING';
    case PARTS = 'PARTS';
    case TIRES = 'TIRES';
    case ELECTRICAL = 'ELECTRICAL';
    case HYDRAULIC = 'HYDRAULIC';
    case LABOR = 'LABOR';
    case TAXES = 'TAXES';
    case RENT = 'RENT';
    case ENERGY = 'ENERGY';
    case FUEL = 'FUEL';
    case MAINTENANCE = 'MAINTENANCE';
    case PAYROLL = 'PAYROLL';
    case ACCOUNTANT = 'ACCOUNTANT';
    case FINANCING = 'FINANCING';
    case OTHER = 'OTHER';

    public function label(): string
    {
        return match($this) {
            self::RAW_MATERIAL => 'Matéria-prima',
            self::METAL_SHEET => 'Chapa metálica',
            self::PROFILE => 'Perfil',
            self::PAINTING => 'Pintura',
            self::PARTS => 'Peças',
            self::TIRES => 'Pneus',
            self::ELECTRICAL => 'Elétrica',
            self::HYDRAULIC => 'Hidráulica',
            self::LABOR => 'Mão de obra',
            self::TAXES => 'Impostos',
            self::RENT => 'Aluguel',
            self::ENERGY => 'Energia',
            self::FUEL => 'Combustível',
            self::MAINTENANCE => 'Manutenção',
            self::PAYROLL => 'Folha salarial',
            self::ACCOUNTANT => 'Contabilidade',
            self::FINANCING => 'Financiamento',
            self::OTHER => 'Outros',
        };
    }
}
