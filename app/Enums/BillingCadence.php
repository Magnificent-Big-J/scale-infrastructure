<?php

namespace App\Enums;

enum BillingCadence: string
{
    case OnceOff = 'once_off';
    case Monthly = 'monthly';
    case Annual = 'annual';

    public function label(): string
    {
        return match ($this) {
            self::OnceOff => 'Once-off',
            self::Monthly => 'Monthly',
            self::Annual => 'Annual',
        };
    }

    public function isRecurring(): bool
    {
        return $this !== self::OnceOff;
    }

    /**
     * Normalised monthly value of an amount billed at this cadence.
     */
    public function monthlyValue(float $amount): float
    {
        return match ($this) {
            self::OnceOff => 0.0,
            self::Monthly => $amount,
            self::Annual => $amount / 12,
        };
    }

    public static function options(): array
    {
        return array_map(fn (self $cadence) => ['value' => $cadence->value, 'label' => $cadence->label()], self::cases());
    }

    public static function values(): array
    {
        return array_map(fn (self $cadence) => $cadence->value, self::cases());
    }
}
