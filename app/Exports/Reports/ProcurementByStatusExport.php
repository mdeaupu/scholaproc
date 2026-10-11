<?php

namespace App\Exports\Reports;

use App\Models\ProcurementRequest;
use Illuminate\Support\Collection;

/**
 * Sheet "Per Status": rekap per status alur kerja. Semua status tetap
 * ditampilkan walau tidak ada transaksinya (zero-filled) supaya urutan alur
 * procurement terlihat utuh.
 */
class ProcurementByStatusExport extends ProcurementReportExport
{
    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    public function __construct(private readonly Collection $rows) {}

    public function sheetTitle(): string
    {
        return 'Per Status';
    }

    public function rows(): Collection
    {
        $total = max($this->rows->sum('total_transactions'), 1);

        $rows = collect([
            ['No', 'Status', 'Kode', 'Jumlah Transaksi', 'Persentase', 'Total Kuantitas', 'Estimasi', 'Resmi', 'Penghematan'],
        ]);

        return $rows->concat($this->rows->values()->map(function (array $r, int $index) use ($total) {
            $estimated = (float) $r['total_estimated'];
            $official = (float) $r['total_official'];

            return [
                $index + 1,
                (string) ($r['label'] ?? ProcurementRequest::statusLabel($r['status'])),
                (string) $r['status'],
                $this->integer($r['total_transactions']),
                round((int) $r['total_transactions'] / $total * 100, 1),
                $this->integer($r['total_quantity']),
                $this->money($estimated),
                $this->money($official),
                $this->money($estimated - $official),
            ];
        })->all());
    }
}
