<?php

namespace App\Contracts;

interface FinanceDashboardServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function metrics(): array;
}
