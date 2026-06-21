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

    /**
     * Multiplier applied to an agreement's response SLA hours: higher severity
     * tightens the target, lower severity relaxes it.
     */
    public function slaFactor(): float
    {
        return match ($this) {
            self::Critical => 0.25,
            self::High => 0.5,
            self::Medium => 1.0,
            self::Low => 2.0,
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
