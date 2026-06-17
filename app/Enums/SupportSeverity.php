<?php

namespace App\Enums;

enum SupportSeverity: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low',
            self::Medium => 'Medium',
            self::High => 'High',
            self::Critical => 'Critical',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Low => 'active',
            self::Medium => 'processing',
            self::High => 'pending',
            self::Critical => 'failed',
        };
    }

    public static function options(): array
    {
        return array_map(fn (self $severity) => ['value' => $severity->value, 'label' => $severity->label()], self::cases());
    }

    public static function values(): array
    {
        return array_map(fn (self $severity) => $severity->value, self::cases());
    }
}
