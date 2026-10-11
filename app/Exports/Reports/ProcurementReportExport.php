<?php

namespace App\Exports\Reports;

use App\Services\ReportExportService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Basis semua sheet laporan (M13).
 *
 * Tiap subclass hanya menyediakan judul sheet dan baris datanya. Penyatuan
 * kelima sheet menjadi satu file .xlsx multitab ditangani
 * {@see ReportExportService} memakai SheetCollection Fast-Excel,
 * sehingga setiap sheet tetap bisa diuji secara terpisah.
 */
abstract class ProcurementReportExport
{
    /**
     * Nama tab di file Excel. Batas 31 karakter dan tanpa karakter \ / * ? : [ ].
     */
    abstract public function sheetTitle(): string;

    /**
     * Baris data sheet. Baris pertama otomatis menjadi header oleh Fast-Excel.
     */
    abstract public function rows(): Collection;

    /**
     * Nama file export (tanpa ekstensi).
     */
    public function filename(): string
    {
        return 'Laporan-Pengadaan-'.$this->sheetTitle();
    }

    /**
     * @return array<string, string|int|float>
     */
    protected function emptyRow(): array
    {
        return [];
    }

    protected function money(float|int|null $value): string
    {
        return 'Rp '.number_format((float) $value, 0, ',', '.');
    }

    protected function integer(int|float|null $value): int
    {
        return (int) $value;
    }

    protected function date(mixed $value): string
    {
        return $value ? Carbon::parse($value)->translatedFormat('d/m/Y') : '-';
    }

    protected function dash(?string $value): string
    {
        return ($value === null || $value === '') ? '-' : $value;
    }
}
