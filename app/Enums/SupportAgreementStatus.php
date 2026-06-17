<?php

namespace App\Enums;

enum SupportAgreementStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Review = 'review';
    case Suspended = 'suspended';
    case Ended = 'ended';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Active => 'Active',
            self::Review => 'Review',
            self::Suspended => 'Suspended',
            self::Ended => 'Ended',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'draft',
            self::Active => 'active',
            self::Review => 'processing',
            self::Suspended => 'suspended',
            self::Ended => 'inactive',
        };
    }

    public static function options(): array
    {
        return array_map(fn (self $status) => ['value' => $status->value, 'label' => $status->label()], self::cases());
    }

    public static function values(): array
    {
        return array_map(fn (self $status) => $status->value, self::cases());
    }
}
