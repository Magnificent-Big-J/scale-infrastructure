<?php

namespace App\Enums;

enum ChangeRisk: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low',
            self::Medium => 'Medium',
            self::High => 'High',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Low => 'active',
            self::Medium => 'pending',
            self::High => 'suspended',
        };
    }

    public static function options(): array
    {
        return array_map(fn (self $risk) => ['value' => $risk->value, 'label' => $risk->label()], self::cases());
    }

    public static function values(): array
    {
        return array_map(fn (self $risk) => $risk->value, self::cases());
    }
}
