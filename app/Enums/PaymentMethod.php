<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Eft = 'eft';
    case Card = 'card';
    case PayFast = 'payfast';
    case DebitOrder = 'debit_order';
    case Cash = 'cash';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Eft => 'EFT',
            self::Card => 'Card',
            self::PayFast => 'PayFast',
            self::DebitOrder => 'Debit order',
            self::Cash => 'Cash',
            self::Other => 'Other',
        };
    }

    public static function options(): array
    {
        return array_map(fn (self $method) => ['value' => $method->value, 'label' => $method->label()], self::cases());
    }

    public static function values(): array
    {
        return array_map(fn (self $method) => $method->value, self::cases());
    }
}
