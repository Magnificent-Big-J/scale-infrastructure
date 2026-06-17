<?php

namespace App\Enums;

enum ClientTier: string
{
    case Starter = 'starter';
    case Growth = 'growth';
    case Enterprise = 'enterprise';

    public function label(): string
    {
        return match ($this) {
            self::Starter => 'Starter',
            self::Growth => 'Growth',
            self::Enterprise => 'Enterprise',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Starter => 'processing',
            self::Growth => 'active',
            self::Enterprise => 'active',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn (self $tier) => ['value' => $tier->value, 'label' => $tier->label()],
            self::cases(),
        );
    }

    public static function values(): array
    {
        return array_map(fn (self $tier) => $tier->value, self::cases());
    }
}
