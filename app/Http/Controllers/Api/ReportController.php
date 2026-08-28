<?php

namespace App\Http\Controllers\Api;

use App\Contracts\ReportServiceInterface;
use App\Enums\ReportType;
use App\Exports\ReportExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function __construct(private readonly ReportServiceInterface $service) {}

    public function index(Request $request): JsonResponse
    {
        $options = collect(ReportType::cases())
            ->filter(fn (ReportType $type) => $this->canView($request, $type))
            ->map(fn (ReportType $type) => $type->toOption())
            ->values();

        return response()->json(['data' => $options]);
    }

    public function show(Request $request, string $type): JsonResponse
    {
        $reportType = $this->resolve($type);
        $this->authorizeReportType($request, $reportType);

        return response()->json(['data' => $this->service->generate($reportType)]);
    }

    public function export(Request $request, string $type): BinaryFileResponse
    {
        $reportType = $this->resolve($type);
        $this->authorizeReportType($request, $reportType);

        return Excel::download(
            new ReportExport($reportType, $this->service),
            "{$reportType->value}-".now()->format('Y-m-d').'.xlsx',
        );
    }

    private function resolve(string $type): ReportType
    {
        return ReportType::tryFrom($type) ?? abort(Response::HTTP_NOT_FOUND);
    }

    private function canView(Request $request, ReportType $type): bool
    {
        $permission = $type->requiredPermission();

        return $permission === null || $request->user()->can($permission);
    }

    private function authorizeReportType(Request $request, ReportType $type): void
    {
        if (! $this->canView($request, $type)) {
            abort(Response::HTTP_FORBIDDEN, "You do not have permission to view the {$type->label()} report.");
        }
    }
}
