<?php

namespace App\Enums;

enum DeploymentStatus: string
{
    case Planned = 'planned';
    case Provisioning = 'provisioning';
    case Active = 'active';
    case Degraded = 'degraded';
    case Retired = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Planned',
            self::Provisioning => 'Provisioning',
            self::Active => 'Active',
            self::Degraded => 'Degraded',
            self::Retired => 'Retired',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Planned => 'processing',
            self::Provisioning => 'processing',
            self::Active => 'active',
            self::Degraded => 'pending',
            self::Retired => 'inactive',
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
