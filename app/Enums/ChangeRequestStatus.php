<?php

namespace App\Enums;

enum ChangeRequestStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Implemented = 'implemented';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Implemented => 'Implemented',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'draft',
            self::Submitted => 'pending',
            self::Approved => 'active',
            self::Rejected => 'suspended',
            self::Implemented => 'processing',
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
