<?php

namespace App\Exports\Reports;

use Illuminate\Support\Collection;

/**
 * Sheet "Per Anak Perusahaan": rekap nilai pengadaan per Anak Perusahaan.
 * Transaksi yang Anak Perusahaan-nya belum ditentukan dikelompokkan ke baris
 * "Belum Ditetapkan Anak Perusahaan".
 */
class ProcurementBySubsidiaryExport extends ProcurementReportExport
{
    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    public function __construct(private readonly Collection $rows) {}

    public function sheetTitle(): string
    {
        return 'Per Anak Perusahaan';
    }

    public function rows(): Collection
    {
        $rows = collect([
            ['No', 'Anak Perusahaan', 'Jumlah Transaksi', 'Total Kuantitas', 'Estimasi', 'Resmi', 'Penghematan', 'Selesai'],
        ]);

        return $rows->concat($this->rows->values()->map(function (array $r, int $index) {
            $estimated = (float) $r['total_estimated'];
            $official = (float) $r['total_official'];

            return [
                $index + 1,
                (string) $r['subsidiary_name'],
                $this->integer($r['total_transactions']),
                $this->integer($r['total_quantity']),
                $this->money($estimated),
                $this->money($official),
                $this->money($estimated - $official),
                $this->integer($r['completed_count']),
            ];
        })->all());
    }
}
