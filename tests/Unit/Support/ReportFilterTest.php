<?php

use App\Support\ReportFilter;
use Illuminate\Support\Carbon;
use Tests\TestCase;

uses(TestCase::class)->group('unit');

it('menampilkan seluruh preset periode yang didukung', function () {
    expect(array_keys(ReportFilter::periodPresets()))->toBe([
        'all',
        'today',
        'this_month',
        'last_month',
        'this_year',
        'last_6_months',
        'custom',
    ]);
});

it('menolak preset periode yang tidak dikenal', function () {
    expect(ReportFilter::isValidPeriod('minggu_lalu'))->toBeFalse()
        ->and(ReportFilter::isValidPeriod(null))->toBeFalse()
        ->and(ReportFilter::isValidPeriod('custom'))->toBeTrue();
});

it('preset all tidak membatasi rentang tanggal', function () {
    expect(ReportFilter::resolvePeriod('all'))->toBe([
        'date_from' => null,
        'date_to' => null,
    ]);
});

it('preset hari ini memakai rentang inklusif satu hari', function () {
    $tz = config('report.timezone', 'Asia/Jakarta');
    $range = ReportFilter::resolvePeriod('today');

    expect($range['date_from'])->toBe(Carbon::today($tz)->toDateString())
        ->and($range['date_to'])->toBe(Carbon::today($tz)->toDateString());
});

it('preset bulan ini dan bulan lalu memakai batas awal/akhir bulan', function () {
    $tz = config('report.timezone', 'Asia/Jakarta');
    $thisMonth = ReportFilter::resolvePeriod('this_month');
    $lastMonth = ReportFilter::resolvePeriod('last_month');

    expect($thisMonth['date_from'])->toBe(Carbon::now($tz)->startOfMonth()->toDateString())
        ->and($thisMonth['date_to'])->toBe(Carbon::now($tz)->endOfMonth()->toDateString())
        ->and($lastMonth['date_from'])->toBe(Carbon::now($tz)->subMonthNoOverflow()->startOfMonth()->toDateString())
        ->and($lastMonth['date_to'])->toBe(Carbon::now($tz)->subMonthNoOverflow()->endOfMonth()->toDateString());
});

it('preset tahun ini dan 6 bulan terakhir dihitung relatif terhadap hari ini', function () {
    $tz = config('report.timezone', 'Asia/Jakarta');
    $thisYear = ReportFilter::resolvePeriod('this_year');
    $sixMonths = ReportFilter::resolvePeriod('last_6_months');

    expect($thisYear['date_from'])->toBe(Carbon::now($tz)->startOfYear()->toDateString())
        ->and($thisYear['date_to'])->toBe(Carbon::now($tz)->endOfYear()->toDateString())
        ->and($sixMonths['date_from'])->toBe(Carbon::now($tz)->subMonthsNoOverflow(6)->toDateString())
        ->and($sixMonths['date_to'])->toBe(Carbon::now($tz)->toDateString());
});

it('preset custom memakai tanggal yang diberikan dan mengabaikan preset lain', function () {
    $range = ReportFilter::resolvePeriod('custom', '2026-01-10', '2026-03-20');

    expect($range)->toBe([
        'date_from' => '2026-01-10',
        'date_to' => '2026-03-20',
    ]);
});

it('preset custom tetap null bila tanggal tidak valid atau kosong', function () {
    expect(ReportFilter::resolvePeriod('custom', '', null))->toBe([
        'date_from' => null,
        'date_to' => null,
    ]);
});

it('normalize membuang nilai kosong agar scope tidak ikut menyaring', function () {
    $filters = ReportFilter::normalize([
        'period' => 'all',
        'date_from' => '',
        'date_to' => null,
        'school_id' => '',
        'supplier_id' => null,
        'status' => [],
    ]);

    expect($filters)->toBe(['period' => 'all']);
});

it('normalize merapikan id, status tunggal, dan rentang custom', function () {
    $filters = ReportFilter::normalize([
        'period' => 'custom',
        'date_from' => '2026-02-01',
        'date_to' => '2026-02-28',
        'school_id' => '7',
        'supplier_id' => '3',
        'status' => 'completed',
    ]);

    expect($filters)->toBe([
        'period' => 'custom',
        'date_from' => '2026-02-01',
        'date_to' => '2026-02-28',
        'school_id' => 7,
        'supplier_id' => 3,
        'status' => ['completed'],
    ]);
});

it('normalize menutup preset yang tidak dikenal kembali ke all', function () {
    expect(ReportFilter::normalize(['period' => 'entah'])['period'])->toBe('all');
});

it('periodLabel menjelaskan rentang custom dalam bahasa manusia', function () {
    $label = ReportFilter::periodLabel([
        'period' => 'custom',
        'date_from' => '2026-02-01',
        'date_to' => '2026-02-28',
    ]);

    expect($label)->toContain('2026');
});

it('periodLabel memakai label preset yang ramah pengguna', function () {
    expect(ReportFilter::periodLabel(['period' => 'this_month']))->toBe('Bulan Ini')
        ->and(ReportFilter::periodLabel(['period' => 'all']))->toBe('Semua Periode');
});

it('periodLabel custom menampilkan kedua tanggal', function () {
    $label = ReportFilter::periodLabel([
        'period' => 'custom',
        'date_from' => '2026-02-01',
        'date_to' => '2026-02-28',
    ]);

    expect($label)->toContain('01')->toContain('28');
});

it('filenameSuffix memakai penanda periode yang aman', function () {
    expect(ReportFilter::filenameSuffix(['date_from' => '2026-01-10', 'date_to' => '2026-03-20']))
        ->toBe('2026-01-10-2026-03-20')
        ->and(ReportFilter::filenameSuffix([]))->toBe('Semua-Periode');
});

it('describe merangkai periode, sekolah, supplier, dan status', function () {
    $description = ReportFilter::describe([
        'period' => 'this_month',
        'school_name' => 'SMA Negeri 1',
        'supplier_name' => 'CV Sumber Rejeki',
        'status' => ['completed'],
        'status_labels' => ['completed' => 'Selesai'],
    ]);

    expect($description)->toContain('Periode: Bulan Ini')
        ->toContain('Sekolah: SMA Negeri 1')
        ->toContain('Supplier: CV Sumber Rejeki')
        ->toContain('Status: Selesai');
});
