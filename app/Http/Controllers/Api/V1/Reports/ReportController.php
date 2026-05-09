<?php

namespace App\Http\Controllers\Api\V1\Reports;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\FinancialReportExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends BaseApiController
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Generate financial reports.
     */
    public function financial(Request $request): JsonResponse|BinaryFileResponse
    {
        $request->validate([
            'type' => ['required', 'in:monthly_summary,ytd_giving,pledge_fulfilment,society_dues,donor_report,annual_statement'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'format' => ['nullable', 'in:json,pdf,excel'],
        ]);

        $type = $request->input('type');
        $format = $request->input('format', 'json');

        // Check permissions
        if ($format === 'json') {
            $this->authorize('reports.view');
        } else {
            $this->authorize('reports.export');
        }

        $data = match ($type) {
            'monthly_summary' => $this->getMonthlySummary($request),
            'ytd_giving' => $this->getYtdGiving($request),
            'pledge_fulfilment' => $this->getPledgeFulfilment($request),
            'society_dues' => $this->getSocietyDues($request),
            'donor_report' => $this->getDonorReport($request),
            'annual_statement' => $this->getAnnualStatement($request),
            default => throw new \InvalidArgumentException('Invalid report type'),
        };

        if ($format === 'json') {
            return $this->success($data);
        }

        if ($format === 'pdf') {
            return $this->generatePdf($type, $data);
        }

        if ($format === 'excel') {
            return $this->generateExcel($type, $data);
        }

        throw new \InvalidArgumentException('Invalid format');
    }

    private function getMonthlySummary(Request $request): array
    {
        $from = $request->input('from') ? Carbon::parse($request->input('from')) : Carbon::now()->startOfYear();
        $to = $request->input('to') ? Carbon::parse($request->input('to')) : Carbon::now()->endOfYear();

        return $this->reportService->monthlySummary($from, $to);
    }

    private function getYtdGiving(Request $request): array
    {
        $from = $request->input('from') ? Carbon::parse($request->input('from')) : Carbon::now()->startOfYear();
        $to = $request->input('to') ? Carbon::parse($request->input('to')) : Carbon::now();

        return $this->reportService->ytdGivingPerMember($from, $to);
    }

    private function getPledgeFulfilment(Request $request): array
    {
        $from = $request->input('from') ? Carbon::parse($request->input('from')) : Carbon::now()->startOfYear();
        $to = $request->input('to') ? Carbon::parse($request->input('to')) : Carbon::now();

        return $this->reportService->pledgeFulfilment($from, $to);
    }

    private function getSocietyDues(Request $request): array
    {
        $year = $request->input('year', Carbon::now()->year);

        return $this->reportService->societyDues($year);
    }

    private function getDonorReport(Request $request): array
    {
        $from = $request->input('from') ? Carbon::parse($request->input('from')) : Carbon::now()->startOfYear();
        $to = $request->input('to') ? Carbon::parse($request->input('to')) : Carbon::now();

        return $this->reportService->donorReport($from, $to);
    }

    private function getAnnualStatement(Request $request): array
    {
        $year = $request->input('year', Carbon::now()->year);

        return $this->reportService->annualStatement($year);
    }

    private function generatePdf(string $type, array $data): BinaryFileResponse
    {
        $view = match ($type) {
            'monthly_summary' => 'reports.financial-summary',
            'pledge_fulfilment' => 'reports.pledge-report',
            'annual_statement' => 'reports.annual-statement',
            default => 'reports.financial-summary',
        };

        $pdf = Pdf::loadView($view, [
            'data' => $data,
            'parish_name' => 'St. Ferdinand Catholic Church, Lagos',
            'logo_path' => public_path('images/parish-logo.png'),
        ]);

        return $pdf->download("{$type}-" . now()->format('Y-m-d') . '.pdf');
    }

    private function generateExcel(string $type, array $data): BinaryFileResponse
    {
        return Excel::download(new FinancialReportExport($type, $data), "{$type}-" . now()->format('Y-m-d') . '.xlsx');
    }
}
