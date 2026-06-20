<?php

namespace App\Contracts;

interface OperationsDashboardServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function metrics(): array;
}
