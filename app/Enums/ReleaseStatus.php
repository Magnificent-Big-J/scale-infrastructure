<?php

namespace App\Enums;

enum ReleaseStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Deploying = 'deploying';
    case Deployed = 'deployed';
    case RolledBack = 'rolled_back';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::PendingApproval => 'Pending approval',
            self::Approved => 'Approved',
            self::Deploying => 'Deploying',
            self::Deployed => 'Deployed',
            self::RolledBack => 'Rolled back',
            self::Failed => 'Failed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'draft',
            self::PendingApproval => 'pending',
            self::Approved => 'processing',
            self::Deploying => 'processing',
            self::Deployed => 'active',
            self::RolledBack => 'suspended',
            self::Failed => 'inactive',
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
