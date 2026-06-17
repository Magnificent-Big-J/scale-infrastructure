<?php

namespace App\Enums;

enum IncidentStatus: string
{
    case Open = 'open';
    case Investigating = 'investigating';
    case Monitoring = 'monitoring';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Investigating => 'Investigating',
            self::Monitoring => 'Monitoring',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'failed',
            self::Investigating => 'pending',
            self::Monitoring => 'processing',
            self::Resolved => 'active',
            self::Closed => 'inactive',
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
