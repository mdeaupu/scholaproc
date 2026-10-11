<?php

namespace App\Exports\Reports;

use Illuminate\Support\Collection;

/**
 * Sheet "Ringkasan": angka-angka agregat beserta nilai turunan (rata-rata,
 * penghematan, proporsi status).
 */
class ProcurementSummaryExport extends ProcurementReportExport
{
    /**
     * @param  array<string, mixed>  $summary
     */
    public function __construct(private readonly array $summary) {}

    public function sheetTitle(): string
    {
        return 'Ringkasan';
    }

    public function rows(): Collection
    {
        $s = $this->summary;
        $total = max((int) $s['total_transactions'], 1);

        return collect([
            ['Keterangan', 'Nilai'],
            ['Jumlah Transaksi', $this->integer($s['total_transactions'])],
            ['Jumlah Item', $this->integer($s['total_item_types'])],
            ['Total Kuantitas', $this->integer($s['total_quantity'])],
            ['Total Estimasi', $this->money($s['total_estimated'])],
            ['Total Resmi', $this->money($s['total_official'])],
            ['Rata-rata Nilai per Transaksi', $this->money($s['average_value'])],
            ['Penghematan (Estimasi - Resmi)', $this->money($s['saving_amount'])],
            ['Transaksi Selesai', $this->integer($s['completed_count'])],
            ['Transaksi Ditolak', $this->integer($s['rejected_count'])],
            ['Transaksi Selesai (%)', round($s['completed_count'] / $total * 100, 1)],
            ['Transaksi Ditolak (%)', round($s['rejected_count'] / $total * 100, 1)],
            ['Total Snapshot Terkunci', $this->integer($s['locked_count'])],
            ['Belum Ditentukan Supplier', $this->integer($s['unsuppliered_count'])],
        ]);
    }
}
