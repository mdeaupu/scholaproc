<?php

use App\Models\ProcurementRequest;
use App\Models\Supplier;
use App\Services\ReportExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function reportService(): ReportExportService
{
    return app(ReportExportService::class);
}

function summaryPayload(): array
{
    return [
        'total_transactions' => 4,
        'total_quantity' => 120,
        'total_item_types' => 2,
        'total_estimated' => 8_000_000,
        'total_official' => 7_500_000,
        'average_value' => 1_875_000,
        'saving_amount' => 500_000,
        'completed_count' => 1,
        'rejected_count' => 1,
        'locked_count' => 1,
        'unsuppliered_count' => 2,
    ];
}

describe('ReportExportService - collectData()', function () {

    it('menyusun seluruh bagian laporan dari satu set filter', function () {
        $request = ProcurementRequest::factory()->create([
            'status' => ProcurementRequest::STATUS_COMPLETED,
        ]);

        $data = reportService()->collectData(['period' => 'all']);

        expect($data)->toHaveKeys([
            'filters',
            'period_label',
            'filter_description',
            'summary',
            'by_school',
            'by_supplier',
            'by_status',
            'transactions',
            'transactions_displayed',
            'transactions_truncated',
            'generated_at',
            'generated_by',
        ])
            ->and($data['summary']['total_transactions'])->toBe(1)
            ->and($data['by_status'])->toHaveCount(count(ProcurementRequest::reportStatuses()))
            ->and($data['transactions'])->toHaveCount(1)
            ->and($data['transactions_displayed'])->toBe(1)
            ->and($data['transactions_truncated'])->toBeFalse()
            ->and($data['transactions']->first()['uuid'])->toBe($request->uuid);
    });

    it('menjelaskan filter aktif dalam bahasa manusia', function () {
        $data = reportService()->collectData([
            'period' => 'this_month',
            'status' => [ProcurementRequest::STATUS_COMPLETED],
        ]);

        expect($data['period_label'])->toBe('Bulan Ini')
            ->and($data['filter_description'])->toContain('Periode: Bulan Ini')
            ->and($data['filter_description'])->toContain('Status: Selesai');
    });

    it('menyertakan nama sekolah dan supplier terpilih untuk deskripsi filter', function () {
        $supplier = Supplier::factory()->create();
        $request = ProcurementRequest::factory()->create(['supplier_id' => $supplier->id]);

        $data = reportService()->collectData([
            'school_id' => $request->school_id,
            'supplier_id' => $supplier->id,
        ]);

        expect($data['filter_description'])->toContain('Sekolah: '.$request->school->name)
            ->and($data['filter_description'])->toContain('Supplier: '.$supplier->company_name);
    });

    it('menghormati batas jumlah baris detail', function () {
        ProcurementRequest::factory()->count(3)->create();

        $data = reportService()->collectData(['period' => 'all'], 2);

        expect($data['transactions'])->toHaveCount(2)
            ->and($data['summary']['total_transactions'])->toBe(3)
            ->and($data['transactions_displayed'])->toBe(2)
            ->and($data['transactions_truncated'])->toBeTrue();
    });

    it('tidak menandai truncation saat seluruh transaksi muat', function () {
        ProcurementRequest::factory()->count(2)->create();

        $data = reportService()->collectData(['period' => 'all'], 5);

        expect($data['transactions_truncated'])->toBeFalse()
            ->and($data['transactions_displayed'])->toBe(2);
    });

});

describe('ReportExportService - sheets()', function () {

    it('menghasilkan lima sheet dengan judul yang sesuai urutan dokumen', function () {
        $sheets = reportService()->sheets(reportService()->collectData(['period' => 'all']));

        expect($sheets->keys()->all())->toBe([
            'Ringkasan',
            'Transaksi',
            'Per Sekolah',
            'Per Supplier',
            'Per Status',
        ]);
    });

    it('setiap sheet diawali baris header', function () {
        $sheets = reportService()->sheets(reportService()->collectData(['period' => 'all']));

        expect($sheets->get('Ringkasan')->first())->toBe(['Keterangan', 'Nilai'])
            ->and($sheets->get('Transaksi')->first()[1])->toBe('No. Pengajuan')
            ->and($sheets->get('Per Sekolah')->first()[1])->toBe('Sekolah')
            ->and($sheets->get('Per Supplier')->first()[1])->toBe('Supplier')
            ->and($sheets->get('Per Status')->first()[1])->toBe('Status');
    });

});

describe('ReportExportService - filename()', function () {

    it('menyertakan periode dan cap waktu pada nama file', function () {
        $filename = reportService()->filename('xlsx', ['period' => 'all']);

        expect($filename)->toStartWith('Laporan-Pengadaan-Semua-Periode-')
            ->and($filename)->toEndWith('.xlsx');
    });

});
