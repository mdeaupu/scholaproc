<?php

use App\Exports\Reports\ProcurementBySchoolExport;
use App\Exports\Reports\ProcurementByStatusExport;
use App\Exports\Reports\ProcurementBySupplierExport;
use App\Exports\Reports\ProcurementSummaryExport;
use App\Exports\Reports\ProcurementTransactionExport;

uses()->group('unit');

function summaryFixture(): array
{
    return [
        'total_transactions' => 10,
        'total_quantity' => 250,
        'total_item_types' => 4,
        'total_estimated' => 12_500_000,
        'total_official' => 11_000_000,
        'average_value' => 1_100_000,
        'saving_amount' => 1_500_000,
        'completed_count' => 4,
        'rejected_count' => 1,
        'locked_count' => 6,
        'unsuppliered_count' => 3,
    ];
}

function transactionFixture(array $overrides = []): array
{
    return array_merge([
        'id' => 1,
        'uuid' => 'uuid-1',
        'school_name' => 'SDN 1 Cianjur',
        'school_npsn' => '20201234',
        'supplier_name' => null,
        'category_name' => 'ATK',
        'budget_year_name' => '2026',
        'status' => 'draft',
        'status_label' => 'Draft',
        'requested_at' => '2026-03-05',
        'created_at' => '2026-03-01',
        'total_quantity' => 20,
        'total_item_types' => 2,
        'total_estimated' => 1_000_000,
        'total_official' => 900_000,
    ], $overrides);
}

it('sheet ringkasan menaruh sumber di kolom pertama dan nilai di kolom kedua', function () {
    $rows = (new ProcurementSummaryExport(summaryFixture()))->rows();

    expect($rows->first())->toBe(['Keterangan', 'Nilai']);

    $values = $rows->pluck(1, 0);

    expect($values['Jumlah Transaksi'])->toBe(10)
        ->and($values['Total Estimasi'])->toBe('Rp 12.500.000')
        ->and($values['Total Resmi'])->toBe('Rp 11.000.000')
        ->and($values['Penghematan (Estimasi - Resmi)'])->toBe('Rp 1.500.000')
        ->and($values['Rata-rata Nilai per Transaksi'])->toBe('Rp 1.100.000')
        ->and($values['Transaksi Selesai (%)'])->toBe(40.0);
});

it('sheet ringkasan tidak membagi dengan nol saat tidak ada transaksi', function () {
    $empty = array_merge(summaryFixture(), [
        'total_transactions' => 0,
        'completed_count' => 0,
        'rejected_count' => 0,
    ]);

    $values = (new ProcurementSummaryExport($empty))->rows()->pluck(1, 0);

    expect($values['Transaksi Selesai (%)'])->toBe(0.0)
        ->and($values['Transaksi Ditolak (%)'])->toBe(0.0);
});

it('sheet transaksi memberi nomor urut, mengurutkan tanggal, dan menghitung selisih', function () {
    $rows = (new ProcurementTransactionExport(collect([
        transactionFixture(),
        transactionFixture([
            'uuid' => 'uuid-2',
            'requested_at' => '2026-04-10',
            'supplier_name' => 'PT Sinar Jaya',
        ]),
    ])))->rows();

    $body = $rows->skip(1);

    expect($rows->first()[1])->toBe('No. Pengajuan')
        ->and($body->first()[0])->toBe(1)
        ->and($body->last()[0])->toBe(2)
        ->and($body->first()[2])->toBe('05/03/2026')
        ->and($body->last()[2])->toBe('10/04/2026')
        ->and($body->first()[5])->toBe('-')
        ->and($body->last()[5])->toBe('PT Sinar Jaya')
        ->and($body->first()[11])->toBe('Rp 1.000.000')
        ->and($body->first()[12])->toBe('Rp 900.000')
        ->and($body->first()[13])->toBe('Rp 100.000');
});

it('sheet transaksi memakai tanggal dibuat saat tanggal pengajuan kosong', function () {
    $rows = (new ProcurementTransactionExport(collect([
        transactionFixture(['requested_at' => null]),
    ])))->rows();

    expect($rows->get(1)[2])->toBe('01/03/2026');
});

it('sheet transaksi tetap menulis header walau tidak ada data', function () {
    $rows = (new ProcurementTransactionExport(collect()))->rows();

    expect($rows)->toHaveCount(1)
        ->and($rows->first()[0])->toBe('No');
});

it('sheet transaksi menambahkan catatan saat detail dipotong', function () {
    $rows = (new ProcurementTransactionExport(collect([
        transactionFixture(),
    ]), 3))->rows();

    expect($rows)->toHaveCount(3)
        ->and($rows->last()[1])->toContain('menampilkan 1 dari 3 transaksi detail');
});

it('sheet transaksi tanpa catatan ketika seluruh baris ditampilkan', function () {
    $rows = (new ProcurementTransactionExport(collect([
        transactionFixture(),
    ]), 1))->rows();

    expect($rows)->toHaveCount(2);
});

it('sheet per sekolah menambahkan penghematan dan urut dari data model', function () {
    $rows = (new ProcurementBySchoolExport(collect([
        [
            'school_id' => 1,
            'school_name' => 'SDN 1 Cianjur',
            'school_npsn' => '20201234',
            'total_transactions' => 3,
            'total_quantity' => 60,
            'total_estimated' => 3_000_000,
            'total_official' => 2_700_000,
            'completed_count' => 2,
            'rejected_count' => 0,
        ],
    ])))->rows();

    expect($rows->first()[1])->toBe('Sekolah')
        ->and($rows->get(1)[0])->toBe(1)
        ->and($rows->get(1)[4])->toBe(60)
        ->and($rows->get(1)[5])->toBe('Rp 3.000.000')
        ->and($rows->get(1)[6])->toBe('Rp 2.700.000')
        ->and($rows->get(1)[7])->toBe('Rp 300.000');
});

it('sheet per supplier memakai nama perusahaan dan jumlah selesai', function () {
    $rows = (new ProcurementBySupplierExport(collect([
        [
            'supplier_id' => 5,
            'company_name' => 'PT Sinar Jaya',
            'total_transactions' => 2,
            'total_quantity' => 40,
            'total_estimated' => 1_000_000,
            'total_official' => 950_000,
            'completed_count' => 1,
        ],
    ])))->rows();

    expect($rows->first()[1])->toBe('Supplier')
        ->and($rows->get(1)[1])->toBe('PT Sinar Jaya')
        ->and($rows->get(1)[6])->toBe('Rp 50.000')
        ->and($rows->get(1)[7])->toBe(1);
});

it('sheet per status menghitung persentase terhadap total transaksi', function () {
    $rows = (new ProcurementByStatusExport(collect([
        ['status' => 'draft', 'label' => 'Draft', 'total_transactions' => 2, 'total_quantity' => 10, 'total_estimated' => 100_000, 'total_official' => 90_000],
        ['status' => 'completed', 'label' => 'Selesai', 'total_transactions' => 3, 'total_quantity' => 30, 'total_estimated' => 300_000, 'total_official' => 280_000],
        ['status' => 'verified', 'label' => 'Diverifikasi', 'total_transactions' => 0, 'total_quantity' => 0, 'total_estimated' => 0, 'total_official' => 0],
    ])))->rows();

    expect($rows->first()[1])->toBe('Status')
        ->and($rows->get(1)[3])->toBe(2)
        ->and($rows->get(1)[4])->toBe(40.0)
        ->and($rows->get(2)[4])->toBe(60.0)
        ->and($rows->get(3)[3])->toBe(0)
        ->and($rows->get(3)[6])->toBe('Rp 0');
});

it('sheet per status tidak membagi dengan nol saat belum ada transaksi', function () {
    $rows = (new ProcurementByStatusExport(collect([
        ['status' => 'draft', 'label' => 'Draft', 'total_transactions' => 0, 'total_quantity' => 0, 'total_estimated' => 0, 'total_official' => 0],
    ])))->rows();

    expect($rows->get(1)[4])->toBe(0.0);
});

it('setiap export memberi judul tab yang singkat dan unik', function () {
    $titles = [
        (new ProcurementSummaryExport(summaryFixture()))->sheetTitle(),
        (new ProcurementTransactionExport(collect()))->sheetTitle(),
        (new ProcurementBySchoolExport(collect()))->sheetTitle(),
        (new ProcurementBySupplierExport(collect()))->sheetTitle(),
        (new ProcurementByStatusExport(collect()))->sheetTitle(),
    ];

    expect($titles)->toBe(['Ringkasan', 'Transaksi', 'Per Sekolah', 'Per Supplier', 'Per Status'])
        ->and(array_unique($titles))->toHaveCount(5);

    foreach ($titles as $title) {
        expect(strlen($title))->toBeLessThanOrEqual(31);
    }
});
