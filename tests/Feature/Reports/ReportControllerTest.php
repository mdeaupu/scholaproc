<?php

use App\Models\ProcurementRequest;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function reportUser(string $role): User
{
    Role::firstOrCreate(['name' => $role]);

    return User::factory()->create(['role' => $role])->assignRole($role);
}

describe('ReportController - auth guard', function () {

    it('mengalihkan tamu ke login untuk halaman laporan', function () {
        $this->get(route('reports.index'))->assertRedirect(route('login'));
    });

    it('menolak pengguna sekolah pada halaman dan endpoint export', function () {
        $user = reportUser('school');

        $this->actingAs($user)->get(route('reports.index'))->assertForbidden();
        $this->actingAs($user)->get(route('reports.summary'))->assertForbidden();
        $this->actingAs($user)->get(route('reports.export.excel'))->assertForbidden();
        $this->actingAs($user)->get(route('reports.export.pdf'))->assertForbidden();
    });

});

describe('ReportController - summary()', function () {

    it('mengalihkan ke viewer laporan beserta filter yang valid', function () {
        $target = ProcurementRequest::factory()->create();

        $response = $this->actingAs(reportUser('admin'))->get(route('reports.summary', [
            'period' => 'custom',
            'date_from' => '2026-05-01',
            'date_to' => '2026-05-31',
            'school_id' => $target->school_id,
            'status' => [ProcurementRequest::STATUS_COMPLETED],
        ]));

        $response->assertRedirect(route('reports.index', [
            'period' => 'custom',
            'date_from' => '2026-05-01',
            'date_to' => '2026-05-31',
            'school_id' => $target->school_id,
            'status' => [ProcurementRequest::STATUS_COMPLETED],
        ]));
        $response->assertSessionHasNoErrors();
    });

    it('mengabaikan parameter kosong saat mengalihkan', function () {
        $response = $this->actingAs(reportUser('superadmin'))->get(route('reports.summary', [
            'period' => 'all',
            'school_id' => '',
        ]));

        $response->assertRedirect(route('reports.index', ['period' => 'all']));
    });

    it('filter yang tidak valid ditolak dengan pesan error', function () {
        $response = $this->actingAs(reportUser('admin'))->get(route('reports.summary', [
            'period' => 'entah',
        ]));

        $response->assertSessionHasErrors('period');
    });

});

describe('ReportController - exportExcel()', function () {

    it('superadmin bisa mengunduh workbook lima sheet', function () {
        $user = reportUser('superadmin');
        ProcurementRequest::factory()->create();

        $response = $this->actingAs($user)->get(route('reports.export.excel'));

        $response->assertOk();
        expect($response->headers->get('content-disposition'))
            ->toContain('attachment')
            ->toContain('Laporan-Pengadaan-Semua-Periode-')
            ->toContain('.xlsx');
    });

    it('admin biasa juga boleh mengunduh laporan', function () {
        $response = $this->actingAs(reportUser('admin'))->get(route('reports.export.excel'));

        $response->assertOk();
    });

    it('filter yang tidak valid ditolak dengan pesan error', function () {
        $response = $this->actingAs(reportUser('admin'))->get(route('reports.export.excel', ['period' => 'entah']));

        $response->assertSessionHasErrors('period');
    });

    it('status di luar workflow ditolak', function () {
        $response = $this->actingAs(reportUser('admin'))->get(route('reports.export.excel', ['status' => ['dibatalkan']]));

        $response->assertSessionHasErrors('status.0');
    });

    it('tanggal custom dengan urutan terbalik ditolak', function () {
        $response = $this->actingAs(reportUser('admin'))->get(route('reports.export.excel', [
            'period' => 'custom',
            'date_from' => '2026-05-01',
            'date_to' => '2026-04-01',
        ]));

        $response->assertSessionHasErrors('date_to');
    });

    it('sekolah yang sudah dihapus tidak bisa dipilih', function () {
        $request = ProcurementRequest::factory()->create();
        $request->school->delete();

        $response = $this->actingAs(reportUser('admin'))->get(route('reports.export.excel', [
            'school_id' => $request->school_id,
        ]));

        $response->assertSessionHasErrors('school_id');
    });

    it('filter status dan sekolah yang valid diteruskan ke laporan', function () {
        $supplier = Supplier::factory()->create();
        $target = ProcurementRequest::factory()->create([
            'supplier_id' => $supplier->id,
            'status' => ProcurementRequest::STATUS_COMPLETED,
        ]);

        $response = $this->actingAs(reportUser('admin'))->get(route('reports.export.excel', [
            'period' => 'all',
            'school_id' => $target->school_id,
            'supplier_id' => $supplier->id,
            'status' => [ProcurementRequest::STATUS_COMPLETED],
        ]));

        $response->assertOk();
        $response->assertSessionHasNoErrors();
    });

});

describe('ReportController - exportPdf()', function () {

    it('menghasilkan berkas PDF landscape berisi judul laporan', function () {
        $user = reportUser('superadmin');
        ProcurementRequest::factory()->create();

        $response = $this->actingAs($user)->get(route('reports.export.pdf'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    });

    it('filter periode tidak dikenal tidak menghasilkan PDF', function () {
        $response = $this->actingAs(reportUser('admin'))->get(route('reports.export.pdf', ['period' => 'besok']));

        $response->assertSessionHasErrors('period');
    });

});
