<?php

namespace App\Exports;

use App\Contracts\ReportServiceInterface;
use App\Enums\ReportType;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * A single export for every report type. The report shape (title, columns,
 * rows) is owned by ReportService, so adding a report never touches this class.
 */
class ReportExport implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    /**
     * @var array<string, mixed>
     */
    private array $report;

    public function __construct(ReportType $type, ReportServiceInterface $service)
    {
        $this->report = $service->generate($type);
    }

    public function array(): array
    {
        return $this->report['rows'];
    }

    public function headings(): array
    {
        return $this->report['columns'];
    }

    public function title(): string
    {
        return $this->report['title'];
    }
}
