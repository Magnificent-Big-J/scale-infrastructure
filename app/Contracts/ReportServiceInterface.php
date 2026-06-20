<?php

namespace App\Contracts;

use App\Enums\ReportType;

interface ReportServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function generate(ReportType $type): array;
}
