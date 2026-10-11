<?php

use App\Livewire\Report\ReportViewer;
use App\Models\ProcurementRequest;
use App\Models\Supplier;
use App\Models\User;
use App\Support\ReportFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function viewerUser(string $role): User
{
    Role::firstOrCreate(['name' => $role]);

    return User::factory()->create(['role' => $role])->assignRole($role);
}

describe('ReportViewer - akses', function () {

    it('hanya superadmin dan admin yang boleh membuka halaman laporan', function () {
        foreach (['superadmin', 'admin'] as $role) {
            $this->actingAs(viewerUser($role));

            Livewire::test(ReportViewer::class)->assertStatus(200);
        }

        $this->actingAs(viewerUser('school'));

        Livewire::test(ReportViewer::class)->assertForbidden();
    });

    it('route halaman laporan menolak pengguna sekolah', function () {
        $this->actingAs(viewerUser('school'))
            ->get(route('reports.index'))
            ->assertForbidden();
    });

});

describe('ReportViewer - ringkasan', function () {

    it('menampilkan seluruh bagian laporan', function () {
        ProcurementRequest::factory()->count(2)->create();

        $this->actingAs(viewerUser('admin'));

        Livewire::test(ReportViewer::class)
            ->assertViewHas('summary')
            ->assertViewHas('bySchool')
            ->assertViewHas('bySupplier')
            ->assertViewHas('byStatus')
            ->assertViewHas('transactions')
            ->assertSee('Laporan Pengadaan')
            ->assertSee('Total Transaksi')
            ->assertSee('Per Sekolah')
            ->assertSee('Per Supplier');
    });

    it('jumlah transaksi pada ringkasan mengikuti data', function () {
        ProcurementRequest::factory()->count(3)->create();

        $this->actingAs(viewerUser('admin'));

        Livewire::test(ReportViewer::class)
            ->assertViewHas('summary', fn (array $summary) => $summary['total_transactions'] === 3);
    });

});

describe('ReportViewer - filter', function () {

    it('menyaring berdasarkan sekolah terpilih', function () {
        $included = ProcurementRequest::factory()->create();
        $excluded = ProcurementRequest::factory()->create();

        $this->actingAs(viewerUser('admin'));

        Livewire::test(ReportViewer::class)
            ->set('school_id', (string) $included->school_id)
            ->assertViewHas('summary', fn (array $summary) => $summary['total_transactions'] === 1)
            ->assertViewHas('transactions', fn ($paginator) => $paginator->count() === 1)
            ->assertSee($included->uuid)
            ->assertDontSee($excluded->uuid);
    });

    it('menyaring berdasarkan status terpilih', function () {
        $completed = ProcurementRequest::factory()->create(['status' => ProcurementRequest::STATUS_COMPLETED]);
        $draft = ProcurementRequest::factory()->create(['status' => ProcurementRequest::STATUS_DRAFT]);

        $this->actingAs(viewerUser('admin'));

        Livewire::test(ReportViewer::class)
            ->set('status', [ProcurementRequest::STATUS_COMPLETED])
            ->assertViewHas('summary', fn (array $summary) => $summary['total_transactions'] === 1)
            ->assertSee($completed->uuid)
            ->assertDontSee($draft->uuid);
    });

    it('preset periode bulan ini menyaring transaksi lama', function () {
        ProcurementRequest::factory()->create([
            'requested_at' => now()->startOfMonth(),
        ]);
        ProcurementRequest::factory()->create([
            'requested_at' => now()->subMonths(3)->startOfMonth(),
        ]);

        $this->actingAs(viewerUser('admin'));

        Livewire::test(ReportViewer::class)
            ->set('period', ReportFilter::THIS_MONTH)
            ->assertViewHas('summary', fn (array $summary) => $summary['total_transactions'] === 1);
    });

    it('memakai rentang tanggal kustom', function () {
        ProcurementRequest::factory()->create(['requested_at' => '2026-02-10']);
        ProcurementRequest::factory()->create(['requested_at' => '2026-06-10']);

        $this->actingAs(viewerUser('admin'));

        Livewire::test(ReportViewer::class)
            ->set('period', ReportFilter::CUSTOM)
            ->set('date_from', '2026-02-01')
            ->set('date_to', '2026-02-28')
            ->assertViewHas('summary', fn (array $summary) => $summary['total_transactions'] === 1);
    });

    it('resetFilters mengembalikan laporan ke seluruh periode', function () {
        $school = ProcurementRequest::factory()->create()->school_id;

        $this->actingAs(viewerUser('admin'));

        Livewire::test(ReportViewer::class)
            ->set('school_id', (string) $school)
            ->call('resetFilters')
            ->assertSet('period', ReportFilter::ALL)
            ->assertSet('school_id', '')
            ->assertSet('status', [])
            ->assertViewHas('summary', fn (array $summary) => $summary['total_transactions'] === 1);
    });

});

describe('ReportViewer - url export', function () {

    it('membawa seluruh filter aktif ke url excel', function () {
        $supplier = Supplier::factory()->create();
        $school = ProcurementRequest::factory()->create()->school_id;

        $this->actingAs(viewerUser('admin'));

        $url = Livewire::test(ReportViewer::class)
            ->set('period', ReportFilter::THIS_YEAR)
            ->set('school_id', (string) $school)
            ->set('supplier_id', (string) $supplier->id)
            ->set('status', [ProcurementRequest::STATUS_COMPLETED])
            ->call('filters')
            ->instance()
            ->exportUrl('excel');

        expect($url)->toContain('reports/export/excel')
            ->toContain('period=this_year')
            ->toContain('school_id='.$school)
            ->toContain('supplier_id='.$supplier->id)
            ->toContain('status%5B0%5D=completed');
    });

    it('tidak menyertakan filter kosong pada url pdf', function () {
        $this->actingAs(viewerUser('admin'));

        $url = Livewire::test(ReportViewer::class)
            ->call('filters')
            ->instance()
            ->exportUrl('pdf');

        expect($url)->toContain('reports/export/pdf')
            ->not->toContain('school_id')
            ->not->toContain('status');
    });

});

describe('ReportViewer - paginasi transaksi', function () {

    it('halaman berikutnya menampilkan transaksi berbeda', function () {
        ProcurementRequest::factory()->count(6)->create();

        $this->actingAs(viewerUser('admin'));

        $component = Livewire::test(ReportViewer::class);

        $firstPage = $component->viewData('transactions')->pluck('uuid')->all();
        $secondPage = Livewire::test(ReportViewer::class)
            ->set('perPage', 3)
            ->call('gotoPage', 2)
            ->viewData('transactions');

        expect($firstPage)->toHaveCount(6)
            ->and($secondPage->total())->toBe(6)
            ->and($secondPage->currentPage())->toBe(2)
            ->and($secondPage->pluck('uuid')->all())->toHaveCount(3);
    });

});
