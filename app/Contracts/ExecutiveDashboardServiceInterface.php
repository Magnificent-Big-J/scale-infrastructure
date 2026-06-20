<?php

namespace App\Contracts;

interface ExecutiveDashboardServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function metrics(): array;
}
