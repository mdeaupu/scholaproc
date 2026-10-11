<?php

namespace App\Services;

use App\Exports\Reports\ProcurementBySchoolExport;
use App\Exports\Reports\ProcurementByStatusExport;
use App\Exports\Reports\ProcurementBySupplierExport;
use App\Exports\Reports\ProcurementReportExport;
use App\Exports\Reports\ProcurementSummaryExport;
use App\Exports\Reports\ProcurementTransactionExport;
use App\Models\ProcurementRequest;
use App\Support\ReportFilter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Rap2hpoutre\FastExcel\Facades\FastExcel;
use Rap2hpoutre\FastExcel\SheetCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Satu-satunya tempat yang menyusun data laporan, dipakai bersama oleh preview
 * Livewire, export PDF, dan export Excel. Filter dinormalisasi ReportFilter
 * sehingga angka di layar selalu sama dengan angka di file.
 */
class ReportExportService
{
    /**
     * Batas baris detail untuk PDF & Excel supaya file tidak membebani memori.
     */
    public const TRANSACTION_LIMIT = 5000;

    /**
     * Kumpulkan seluruh data laporan untuk satu set filter.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function collectData(array $filters, ?int $transactionLimit = null): array
    {
        $filters = ReportFilter::normalize($filters);

        $filters['status_labels'] = ProcurementRequest::statusLabels();
        $filters['school_name'] = $this->relationName('schools', $filters['school_id'] ?? null, 'name');
        $filters['supplier_name'] = $this->relationName('suppliers', $filters['supplier_id'] ?? null, 'company_name');
        $filters['filter_description'] = ReportFilter::describe($filters);

        $summary = ProcurementRequest::reportSummary(ProcurementRequest::forReport($filters));

        $transactions = ProcurementRequest::reportTransactions(
            ProcurementRequest::forReport($filters),
            $transactionLimit ?? self::TRANSACTION_LIMIT
        );

        $displayed = $transactions->count();
        $total = (int) $summary['total_transactions'];

        return [
            'filters' => $filters,
            'period_label' => ReportFilter::periodLabel($filters),
            'filter_description' => $filters['filter_description'],
            'summary' => $summary,
            'by_school' => ProcurementRequest::reportBySchool(ProcurementRequest::forReport($filters)),
            'by_supplier' => ProcurementRequest::reportBySupplier(ProcurementRequest::forReport($filters)),
            'by_status' => ProcurementRequest::reportByStatus(ProcurementRequest::forReport($filters)),
            'transactions' => $transactions,
            'transactions_displayed' => $displayed,
            'transactions_truncated' => $displayed < $total,
            'generated_at' => now(),
            'generated_by' => Auth::user()?->name ?? '-',
        ];
    }

    /**
     * Kelima sheet laporan dalam satu file .xlsx multitab.
     *
     * @param  array<string, mixed>  $data
     */
    public function sheets(array $data): SheetCollection
    {
        /** @var list<ProcurementReportExport> $exports */
        $exports = [
            new ProcurementSummaryExport($data['summary']),
            new ProcurementTransactionExport($data['transactions'], (int) $data['summary']['total_transactions']),
            new ProcurementBySchoolExport($data['by_school']),
            new ProcurementBySupplierExport($data['by_supplier']),
            new ProcurementByStatusExport($data['by_status']),
        ];

        $sheets = new SheetCollection;

        foreach ($exports as $export) {
            $sheets->put($export->sheetTitle(), $export->rows());
        }

        return $sheets;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function exportExcel(array $filters, ?int $transactionLimit = null): StreamedResponse
    {
        $data = $this->collectData($filters, $transactionLimit);

        return FastExcel::data($this->sheets($data))
            ->download($this->filename('xlsx', $data['filters']));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function exportPdf(array $filters, ?int $transactionLimit = null): Response
    {
        $data = $this->collectData($filters, $transactionLimit);

        $pdf = Pdf::loadView('reports.pdf', $data)
            ->setPaper('a4', 'landscape');

        return $pdf->download($this->filename('pdf', $data['filters']));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filename(string $format, array $filters): string
    {
        $suffix = ReportFilter::filenameSuffix($filters);

        return 'Laporan-Pengadaan-'.$suffix.'-'.now()->format('Ymd-His').'.'.$format;
    }

    private function relationName(string $table, mixed $id, string $column): ?string
    {
        if (! is_numeric($id) || (int) $id <= 0) {
            return null;
        }

        return DB::table($table)->where('id', (int) $id)->value($column);
    }
}
