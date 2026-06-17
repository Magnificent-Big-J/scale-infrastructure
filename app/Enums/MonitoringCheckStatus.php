<?php

namespace App\Enums;

enum MonitoringCheckStatus: string
{
    case Passing = 'passing';
    case Warning = 'warning';
    case Failing = 'failing';
    case Paused = 'paused';

    public function label(): string
    {
        return match ($this) {
            self::Passing => 'Passing',
            self::Warning => 'Warning',
            self::Failing => 'Failing',
            self::Paused => 'Paused',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Passing => 'active',
            self::Warning => 'pending',
            self::Failing => 'failed',
            self::Paused => 'inactive',
        };
    }

    public static function options(): array
    {
        return array_map(fn (self $status) => [
            'value' => $status->value,
            'label' => $status->label(),
        ], self::cases());
    }

    public static function values(): array
    {
        return array_map(fn (self $status) => $status->value, self::cases());
    }
}
