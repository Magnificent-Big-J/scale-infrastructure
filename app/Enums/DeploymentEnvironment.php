<?php

namespace App\Enums;

enum DeploymentEnvironment: string
{
    case Production = 'production';
    case Staging = 'staging';
    case Uat = 'uat';
    case Development = 'development';

    public function label(): string
    {
        return match ($this) {
            self::Production => 'Production',
            self::Staging => 'Staging',
            self::Uat => 'UAT',
            self::Development => 'Development',
        };
    }

    public static function options(): array
    {
        return array_map(fn (self $environment) => [
            'value' => $environment->value,
            'label' => $environment->label(),
        ], self::cases());
    }

    public static function values(): array
    {
        return array_map(fn (self $environment) => $environment->value, self::cases());
    }
}
