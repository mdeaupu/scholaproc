<?php

namespace App\Exports\Reports;

use Illuminate\Support\Collection;

/**
 * Sheet "Per Supplier": rekap nilai pengadaan per supplier. Transaksi yang
 * supplier-nya belum ditentukan tidak termasuk, mengikuti reportBySupplier().
 */
class ProcurementBySupplierExport extends ProcurementReportExport
{
    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    public function __construct(private readonly Collection $rows) {}

    public function sheetTitle(): string
    {
        return 'Per Supplier';
    }

    public function rows(): Collection
    {
        $rows = collect([
            ['No', 'Supplier', 'Jumlah Transaksi', 'Total Kuantitas', 'Estimasi', 'Resmi', 'Penghematan', 'Selesai'],
        ]);

        return $rows->concat($this->rows->values()->map(function (array $r, int $index) {
            $estimated = (float) $r['total_estimated'];
            $official = (float) $r['total_official'];

            return [
                $index + 1,
                (string) $r['company_name'],
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
