<?php

namespace App\Enums;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * Names the managed reference-data lists. The *set of lists* is code-controlled
 * (this enum); the *values* inside each list are business-owned data living in
 * the `lookup_options` table. Add a case here to introduce a new managed
 * vocabulary, then seed its defaults.
 */
enum LookupType: string
{
    case TicketCategory = 'ticket_category';
    case OpportunitySource = 'opportunity_source';

    public function label(): string
    {
        return match ($this) {
            self::TicketCategory => 'Ticket category',
            self::OpportunitySource => 'Opportunity source',
        };
    }

    /**
     * Short helper text shown on the reference-data admin screen.
     */
    public function description(): string
    {
        return match ($this) {
            self::TicketCategory => 'Selectable categories used to classify support tickets.',
            self::OpportunitySource => 'Where a sales opportunity originated.',
        };
    }

    /**
     * Validation rule asserting a value is an active option of this list.
     * Reuse on any form field backed by this managed vocabulary.
     */
    public function existsRule(): Exists
    {
        return Rule::exists('lookup_options', 'value')
            ->where('type', $this->value)
            ->where('is_active', true);
    }

    /**
     * @return list<array{value: string, label: string, description: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $type) => [
            'value' => $type->value,
            'label' => $type->label(),
            'description' => $type->description(),
        ], self::cases());
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $type) => $type->value, self::cases());
    }
}
