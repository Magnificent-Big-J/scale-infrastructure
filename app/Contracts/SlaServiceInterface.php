<?php

namespace App\Contracts;

use Illuminate\Support\Collection;

interface SlaServiceInterface
{
    /**
     * SLA overview across tickets that carry a support agreement: the tickets
     * ordered by SLA urgency, plus headline status counts.
     *
     * @return array{tickets: Collection, summary: array<string, int>}
     */
    public function overview(): array;
}
