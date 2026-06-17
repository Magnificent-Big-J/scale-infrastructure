<?php

namespace App\Enums;

enum InfrastructureAssetType: string
{
    case AppServer = 'app_server';
    case Database = 'database';
    case Cache = 'cache';
    case Storage = 'storage';
    case Queue = 'queue';
    case Domain = 'domain';

    public function label(): string
    {
        return match ($this) {
            self::AppServer => 'App server',
            self::Database => 'Database',
            self::Cache => 'Cache',
            self::Storage => 'Storage',
            self::Queue => 'Queue',
            self::Domain => 'Domain',
        };
    }

    public static function options(): array
    {
        return array_map(fn (self $type) => [
            'value' => $type->value,
            'label' => $type->label(),
        ], self::cases());
    }

    public static function values(): array
    {
        return array_map(fn (self $type) => $type->value, self::cases());
    }
}
