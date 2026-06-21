<?php

namespace App\Enums;

enum OpportunityStage: string
{
    case Lead = 'lead';
    case Qualified = 'qualified';
    case Proposal = 'proposal';
    case Negotiation = 'negotiation';
    case Won = 'won';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::Lead => 'Lead',
            self::Qualified => 'Qualified',
            self::Proposal => 'Proposal',
            self::Negotiation => 'Negotiation',
            self::Won => 'Won',
            self::Lost => 'Lost',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Lead, self::Qualified => 'processing',
            self::Proposal, self::Negotiation => 'pending',
            self::Won => 'active',
            self::Lost => 'suspended',
        };
    }

    public function isClosed(): bool
    {
        return in_array($this, [self::Won, self::Lost], true);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $stage) => ['value' => $stage->value, 'label' => $stage->label()], self::cases());
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $stage) => $stage->value, self::cases());
    }
}
