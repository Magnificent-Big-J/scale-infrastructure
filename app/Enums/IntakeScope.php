<?php

namespace App\Enums;

enum IntakeScope: string
{
    case TicketsCreate = 'tickets.create';

    public function label(): string
    {
        return match ($this) {
            self::TicketsCreate => 'Create support tickets',
        };
    }
}
