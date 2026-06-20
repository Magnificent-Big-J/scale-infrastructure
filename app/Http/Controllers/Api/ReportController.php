<?php

namespace App\Http\Controllers\Api;

use App\Contracts\ReportServiceInterface;
use App\Enums\ReportType;
use App\Exports\ReportExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function __construct(private readonly ReportServiceInterface $service) {}

    public function index(): JsonResponse
    {
        return response()->json(['data' => ReportType::options()]);
    }

    public function show(string $type): JsonResponse
    {
        return response()->json(['data' => $this->service->generate($this->resolve($type))]);
    }

    public function export(string $type): BinaryFileResponse
    {
        $reportType = $this->resolve($type);

        return Excel::download(
            new ReportExport($reportType, $this->service),
            "{$reportType->value}-".now()->format('Y-m-d').'.xlsx',
        );
    }

    private function resolve(string $type): ReportType
    {
        return ReportType::tryFrom($type) ?? abort(Response::HTTP_NOT_FOUND);
    }
}
