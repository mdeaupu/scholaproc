<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportFilterRequest;
use App\Services\ReportExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controller tradisional (M13b) untuk laporan. Halaman preview-nya dipegang
 * Livewire ReportViewer; controller ini melayani download dan meneruskan
 * filter valid ke viewer.
 */
class ReportController extends Controller
{
    public function __construct(private readonly ReportExportService $reportExportService) {}

    /**
     * Entry point ringkasan: cukup redirect ke Livewire ReportViewer dengan
     * filter ternormalisasi, sesuai arsitektur M13b.
     */
    public function summary(ReportFilterRequest $request): RedirectResponse
    {
        $filters = array_filter(
            $request->validated(),
            fn ($value) => $value !== null && $value !== [] && $value !== ''
        );

        return redirect()->route('reports.index', $filters);
    }

    public function exportExcel(ReportFilterRequest $request): StreamedResponse
    {
        return $this->reportExportService->exportExcel($request->filters());
    }

    public function exportPdf(ReportFilterRequest $request): Response
    {
        return $this->reportExportService->exportPdf($request->filters());
    }
}
