<?php

namespace App\Enums;

enum AutomationRunStatus: string
{
    case Queued = 'queued';
    case Running = 'running';
    case Succeeded = 'succeeded';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Queued => 'Queued',
            self::Running => 'Running',
            self::Succeeded => 'Succeeded',
            self::Failed => 'Failed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Queued => 'draft',
            self::Running => 'processing',
            self::Succeeded => 'active',
            self::Failed => 'suspended',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Succeeded, self::Failed], true);
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
