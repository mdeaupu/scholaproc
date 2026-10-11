<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Arr;

/**
 * Normalisasi filter laporan (M13).
 *
 * Satu-satunya sumber kebenaran untuk enumerasi preset periode, dipakai bersama oleh
 * Livewire ReportViewer (preview di layar) dan ReportFilterRequest (isi file
 * export) supaya angka yang dilihat user persis sama dengan angka yang diunduh.
 */
class ReportFilter
{
    public const ALL = 'all';

    public const TODAY = 'today';

    public const THIS_MONTH = 'this_month';

    public const LAST_MONTH = 'last_month';

    public const THIS_YEAR = 'this_year';

    public const LAST_6_MONTHS = 'last_6_months';

    public const CUSTOM = 'custom';

    /**
     * @return array<string, string>
     */
    public static function periodPresets(): array
    {
        return [
            self::ALL => 'Semua Periode',
            self::TODAY => 'Hari Ini',
            self::THIS_MONTH => 'Bulan Ini',
            self::LAST_MONTH => 'Bulan Lalu',
            self::THIS_YEAR => 'Tahun Ini',
            self::LAST_6_MONTHS => '6 Bulan Terakhir',
            self::CUSTOM => 'Rentang Kustom',
        ];
    }

    public static function defaultPeriod(): string
    {
        return self::ALL;
    }

    public static function isValidPeriod(?string $period): bool
    {
        return $period !== null && array_key_exists($period, self::periodPresets());
    }

    /**
     * Ubah preset periode menjadi rentang tanggal konkret.
     *
     * @return array{date_from: ?string, date_to: ?string}
     */
    public static function resolvePeriod(?string $period, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $period = self::isValidPeriod($period) ? $period : self::defaultPeriod();

        if ($period === self::CUSTOM) {
            return [
                'date_from' => self::normalizeDate($dateFrom),
                'date_to' => self::normalizeDate($dateTo),
            ];
        }

        $timezone = config('report.timezone', 'Asia/Jakarta');

        $range = match ($period) {
            self::TODAY => [Carbon::today($timezone), Carbon::today($timezone)],
            self::THIS_MONTH => [Carbon::now($timezone)->startOfMonth(), Carbon::now($timezone)->endOfMonth()],
            self::LAST_MONTH => [
                Carbon::now($timezone)->subMonthNoOverflow()->startOfMonth(),
                Carbon::now($timezone)->subMonthNoOverflow()->endOfMonth(),
            ],
            self::THIS_YEAR => [Carbon::now($timezone)->startOfYear(), Carbon::now($timezone)->endOfYear()],
            self::LAST_6_MONTHS => [Carbon::now($timezone)->subMonthsNoOverflow(6), Carbon::now($timezone)],
            default => [null, null],
        };

        return [
            'date_from' => $range[0]?->toDateString(),
            'date_to' => $range[1]?->toDateString(),
        ];
    }

    /**
     * Filter siap pakai yang hanya berisi nilai non-kosong, sesuai apa yang
     * diterima ProcurementRequest::scopeForReport().
     *
     * @return array<string, mixed>
     */
    public static function normalize(array $input): array
    {
        $period = Arr::get($input, 'period');
        $range = self::resolvePeriod(
            $period,
            Arr::get($input, 'date_from'),
            Arr::get($input, 'date_to'),
        );

        $status = Arr::get($input, 'status', []);
        $status = is_string($status) ? [$status] : (array) $status;
        $status = array_values(array_filter($status, fn ($value) => is_string($value) && $value !== ''));

        return array_filter([
            'period' => self::isValidPeriod($period) ? $period : self::defaultPeriod(),
            'date_from' => $range['date_from'],
            'date_to' => $range['date_to'],
            'school_id' => self::normalizeId(Arr::get($input, 'school_id')),
            'supplier_id' => self::normalizeId(Arr::get($input, 'supplier_id')),
            'status' => $status,
        ], fn ($value) => $value !== null && $value !== []);
    }

    /**
     * Ringkasan filter dalam bahasa manusia, dicetak di PDF dan ditampilkan
     * pada halaman laporan.
     *
     * @param  array<string, mixed>  $filters
     */
    public static function describe(array $filters): string
    {
        $parts = ['Periode: '.self::periodLabel($filters)];

        if (! empty($filters['school_name'])) {
            $parts[] = 'Sekolah: '.$filters['school_name'];
        }

        if (! empty($filters['supplier_name'])) {
            $parts[] = 'Supplier: '.$filters['supplier_name'];
        }

        if (! empty($filters['status']) && is_array($filters['status'])) {
            $labels = array_map(fn (string $status) => $filters['status_labels'][$status] ?? $status, $filters['status']);
            $parts[] = 'Status: '.implode(', ', $labels);
        }

        return implode(' · ', $parts);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public static function periodLabel(array $filters): string
    {
        $presets = self::periodPresets();
        $period = Arr::get($filters, 'period');
        $label = $presets[$period] ?? $presets[self::ALL];

        if (($period ?? null) !== self::CUSTOM) {
            return $label;
        }

        $from = Arr::get($filters, 'date_from');
        $to = Arr::get($filters, 'date_to');

        if (! $from && ! $to) {
            return $label;
        }

        return trim(($from ? Carbon::parse($from)->translatedFormat('d M Y') : '…').' s.d. '.($to ? Carbon::parse($to)->translatedFormat('d M Y') : '…'));
    }

    /**
     * Slug aman untuk nama file export.
     *
     * @param  array<string, mixed>  $filters
     */
    public static function filenameSuffix(array $filters): string
    {
        $from = Arr::get($filters, 'date_from');
        $to = Arr::get($filters, 'date_to');

        if (! $from && ! $to) {
            return 'Semua-Periode';
        }

        return trim(($from ?? 'awal').'-'.($to ?? 'akhir'));
    }

    private static function normalizeDate(?string $date): ?string
    {
        if (! $date) {
            return null;
        }

        try {
            return Carbon::parse($date)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private static function normalizeId(mixed $value): ?int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }
}
