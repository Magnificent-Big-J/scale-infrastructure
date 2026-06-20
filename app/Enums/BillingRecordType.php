<?php

namespace App\Enums;

enum BillingRecordType: string
{
    case Assessment = 'assessment';
    case Implementation = 'implementation';
    case Support = 'support';
    case Hosting = 'hosting';
    case Enhancement = 'enhancement';
    case Consulting = 'consulting';

    public function label(): string
    {
        return match ($this) {
            self::Assessment => 'Assessment',
            self::Implementation => 'Implementation',
            self::Support => 'Support',
            self::Hosting => 'Hosting',
            self::Enhancement => 'Enhancement',
            self::Consulting => 'Consulting',
        };
    }

    public static function options(): array
    {
        return array_map(fn (self $type) => ['value' => $type->value, 'label' => $type->label()], self::cases());
    }

    public static function values(): array
    {
        return array_map(fn (self $type) => $type->value, self::cases());
    }
}
