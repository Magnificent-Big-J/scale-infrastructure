<?php

namespace App\Enums;

enum ClientStatus: string
{
    case Prospect = 'prospect';
    case Onboarding = 'onboarding';
    case Active = 'active';
    case AtRisk = 'at_risk';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Prospect => 'Prospect',
            self::Onboarding => 'Onboarding',
            self::Active => 'Active',
            self::AtRisk => 'At risk',
            self::Archived => 'Archived',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Prospect => 'processing',
            self::Onboarding => 'processing',
            self::Active => 'active',
            self::AtRisk => 'pending',
            self::Archived => 'inactive',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn (self $status) => ['value' => $status->value, 'label' => $status->label()],
            self::cases(),
        );
    }

    public static function values(): array
    {
        return array_map(fn (self $status) => $status->value, self::cases());
    }
}
