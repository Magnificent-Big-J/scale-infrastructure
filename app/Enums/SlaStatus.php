<?php

namespace App\Enums;

enum SlaStatus: string
{
    case OnTrack = 'on_track';
    case AtRisk = 'at_risk';
    case Breached = 'breached';
    case Met = 'met';
    case None = 'none';

    public function label(): string
    {
        return match ($this) {
            self::OnTrack => 'On track',
            self::AtRisk => 'At risk',
            self::Breached => 'Breached',
            self::Met => 'Met',
            self::None => 'No SLA',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::OnTrack => 'processing',
            self::AtRisk => 'pending',
            self::Breached => 'suspended',
            self::Met => 'active',
            self::None => 'inactive',
        };
    }

    /**
     * Urgency ordering for SLA-first sorting (lower = more urgent).
     */
    public function rank(): int
    {
        return match ($this) {
            self::Breached => 0,
            self::AtRisk => 1,
            self::OnTrack => 2,
            self::Met => 3,
            self::None => 4,
        };
    }
}
