<?php

namespace App\Http\Controllers\Api;

use App\Contracts\ReportServiceInterface;
use App\Enums\ReportType;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private readonly ReportServiceInterface $service) {}

    public function index(): JsonResponse
    {
        return response()->json(['data' => ReportType::options()]);
    }

    public function show(string $type): JsonResponse
    {
        $reportType = ReportType::tryFrom($type);

        abort_if($reportType === null, Response::HTTP_NOT_FOUND);

        return response()->json(['data' => $this->service->generate($reportType)]);
    }

    public function export(string $type): StreamedResponse
    {
        $reportType = ReportType::tryFrom($type);

        abort_if($reportType === null, Response::HTTP_NOT_FOUND);

        $csv = $this->service->toCsv($reportType);
        $filename = "{$reportType->value}-".now()->format('Y-m-d').'.csv';

        return response()->streamDownload(fn () => print ($csv), $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
