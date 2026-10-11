<?php

namespace App\Exports\Reports;

use Illuminate\Support\Collection;

/**
 * Sheet "Per Sekolah": rekap nilai pengadaan per sekolah, urut dari nilai
 * resmi terbesar.
 */
class ProcurementBySchoolExport extends ProcurementReportExport
{
    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    public function __construct(private readonly Collection $rows) {}

    public function sheetTitle(): string
    {
        return 'Per Sekolah';
    }

    public function rows(): Collection
    {
        $rows = collect([
            [
                'No', 'Sekolah', 'NPSN', 'Jumlah Transaksi', 'Total Kuantitas',
                'Estimasi', 'Resmi', 'Penghematan', 'Selesai', 'Ditolak',
            ],
        ]);

        return $rows->concat($this->rows->values()->map(function (array $r, int $index) {
            $estimated = (float) $r['total_estimated'];
            $official = (float) $r['total_official'];

            return [
                $index + 1,
                (string) $r['school_name'],
                (string) $r['school_npsn'],
                $this->integer($r['total_transactions']),
                $this->integer($r['total_quantity']),
                $this->money($estimated),
                $this->money($official),
                $this->money($estimated - $official),
                $this->integer($r['completed_count']),
                $this->integer($r['rejected_count']),
            ];
        })->all());
    }
}
