<?php

namespace App\Exports\Reports;

use Illuminate\Support\Collection;

/**
 * Sheet "Transaksi": daftar transaksi hasil filter beserta nilai estimasi dan
 * nilai resmi yang sudah konsisten dengan ringkasan.
 */
class ProcurementTransactionExport extends ProcurementReportExport
{
    /**
     * @param  Collection<int, array<string, mixed>>  $transactions
     * @param  int|null  $totalTransactions  total transaksi tanpa batas baris,
     *                                       untuk menandai sheet bila detail dipotong
     */
    public function __construct(
        private readonly Collection $transactions,
        private readonly ?int $totalTransactions = null,
    ) {}

    public function sheetTitle(): string
    {
        return 'Transaksi';
    }

    public function rows(): Collection
    {
        $rows = collect([
            [
                'No', 'No. Pengajuan', 'Tanggal Pengajuan', 'Sekolah', 'NPSN', 'Supplier',
                'Kategori', 'Tahun Anggaran', 'Status', 'Jumlah Item', 'Total Kuantitas',
                'Estimasi', 'Resmi', 'Selisih',
            ],
        ]);

        $rows = $rows->concat($this->transactions->values()->map(function (array $t, int $index) {
            $estimated = (float) $t['total_estimated'];
            $official = (float) $t['total_official'];

            return [
                $index + 1,
                (string) $t['uuid'],
                $this->date($t['requested_at'] ?? $t['created_at']),
                (string) $t['school_name'],
                (string) $t['school_npsn'],
                $this->dash($t['supplier_name']),
                (string) $t['category_name'],
                (string) $t['budget_year_name'],
                (string) $t['status_label'],
                $this->integer($t['total_item_types']),
                $this->integer($t['total_quantity']),
                $this->money($estimated),
                $this->money($official),
                $this->money($estimated - $official),
            ];
        })->all());

        if ($this->totalTransactions !== null && $this->transactions->count() < $this->totalTransactions) {
            $rows = $rows->concat([[
                '',
                'Catatan: menampilkan '.$this->transactions->count().' dari '.$this->totalTransactions
                    .' transaksi detail; rekap & ringkasan mencakup seluruh data.',
            ]]);
        }

        return $rows;
    }
}
