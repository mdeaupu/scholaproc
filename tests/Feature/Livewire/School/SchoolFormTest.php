<?php

use App\Livewire\School\SchoolForm;
use App\Models\FundingSource;
use App\Models\GeneratedDocument;
use App\Models\ProcurementRequest;
use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use function Pest\Laravel\{actingAs};

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'school', 'guard_name' => 'web']);
});

test('superadmin dapat mendaftarkan sekolah baru sekaligus data kop surat dan admin otomatis', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    actingAs($superadmin);

    Livewire::test(SchoolForm::class)
        ->set('npsn', '12345678')
        ->set('name', 'SMA Bina Bangsa')
        ->set('address', 'Jl. Merdeka No. 45')
        ->set('postal_code', '43251')
        ->set('phone_number', '0263998877')
        ->set('email', 'kontak@binabangsa.sch.id')
        ->set('status', 'active')
        ->set('kop_pusat', 'Yayasan Bina Bangsa Pusat')
        ->set('kop_provinsi', 'Regional Jawa Barat')
        ->set('kop_sub_wilayah', 'Cabang Cianjur')
        ->set('password', 'secret123')
        ->call('save')
        ->assertRedirect(route('schools.index'));

    $this->assertDatabaseHas('schools', [
        'npsn' => '12345678',
        'name' => 'SMA Bina Bangsa'
    ]);

    $school = School::where('npsn', '12345678')->first();

    $this->assertDatabaseHas('school_settings', [
        'school_id' => $school->id,
        'kop_pusat' => 'Yayasan Bina Bangsa Pusat'
    ]);

    $this->assertDatabaseHas('users', [
        'school_id' => $school->id,
        'role' => 'school',
        'username' => '12345678',
        'name' => 'Admin SMA Bina Bangsa'
    ]);

    $admin = User::where('school_id', $school->id)->where('role', 'school')->first();
    expect(Hash::check('secret123', $admin->password))->toBeTrue();
});

test('aturan validasi form diterapkan dengan benar saat pendaftaran', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    actingAs($superadmin);

    Livewire::test(SchoolForm::class)
        ->set('npsn', 'tulisan-bukan-angka')
        ->set('name', '')
        ->set('password', 'short')
        ->call('save')
        ->assertHasErrors([
            'npsn' => 'numeric',
            'name' => 'required',
            'password' => 'min'
        ]);
});

test('password wajib diisi saat create', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    actingAs($superadmin);

    Livewire::test(SchoolForm::class)
        ->set('npsn', '12345678')
        ->set('name', 'SMA Test')
        ->set('address', 'Jl. Test')
        ->set('phone_number', '123')
        ->set('kop_pusat', 'Kop')
        ->set('kop_provinsi', 'Prov')
        ->set('password', '')
        ->call('save')
        ->assertHasErrors(['password' => 'required']);
});

test('superadmin dapat mengubah informasi sekolah dan kop surat yang sudah ada', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    $school = School::factory()->create([
        'npsn' => '87654321',
        'name' => 'Nama Sekolah Lama'
    ]);

    $setting = SchoolSetting::factory()->create([
        'school_id' => $school->id,
        'kop_pusat' => 'Kop Pusat Lama'
    ]);

    $admin = User::factory()->create([
        'school_id' => $school->id,
        'role' => 'school',
        'username' => '87654321',
        'password' => Hash::make('oldpassword')
    ]);

    actingAs($superadmin);

    Livewire::test(SchoolForm::class, ['school' => $school])
        ->assertSet('name', 'Nama Sekolah Lama')
        ->assertSet('isEdit', true)
        ->set('name', 'Nama Sekolah Hasil Update')
        ->set('kop_pusat', 'Kop Pusat Hasil Update')
        ->set('password', '')
        ->call('save')
        ->assertRedirect(route('schools.index'));

    expect($school->fresh()->name)->toBe('Nama Sekolah Hasil Update')
        ->and($school->setting->fresh()->kop_pusat)->toBe('Kop Pusat Hasil Update')
        ->and(Hash::check('oldpassword', $admin->fresh()->password))->toBeTrue();
});

test('update password mengubah password admin', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    $school = School::factory()->create([
        'npsn' => '22222222',
        'name' => 'Sekolah B'
    ]);

    SchoolSetting::factory()->create(['school_id' => $school->id]);

    $admin = User::factory()->create([
        'school_id' => $school->id,
        'role' => 'school',
        'username' => '22222222',
        'password' => Hash::make('oldpass')
    ]);

    actingAs($superadmin);

    Livewire::test(SchoolForm::class, ['school' => $school])
        ->set('npsn', '22222222')
        ->set('name', 'Sekolah B')
        ->set('address', 'Jl. Baru')
        ->set('phone_number', '123')
        ->set('kop_pusat', 'Kop')
        ->set('kop_provinsi', 'Prov')
        ->set('password', 'newsecret123')
        ->call('save')
        ->assertRedirect(route('schools.index'));

    expect(Hash::check('newsecret123', $admin->fresh()->password))->toBeTrue();
});

test('update tanpa password mempertahankan password lama', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    $school = School::factory()->create([
        'npsn' => '33333333',
        'name' => 'Sekolah C'
    ]);

    SchoolSetting::factory()->create(['school_id' => $school->id]);

    $admin = User::factory()->create([
        'school_id' => $school->id,
        'role' => 'school',
        'username' => '33333333',
        'password' => Hash::make('oldpass')
    ]);

    actingAs($superadmin);

    Livewire::test(SchoolForm::class, ['school' => $school])
        ->set('npsn', '33333333')
        ->set('name', 'Nama Baru')
        ->set('address', 'Jl. Baru')
        ->set('phone_number', '123')
        ->set('kop_pusat', 'Kop')
        ->set('kop_provinsi', 'Prov')
        ->set('password', '')
        ->call('save')
        ->assertRedirect(route('schools.index'));

    expect(Hash::check('oldpass', $admin->fresh()->password))->toBeTrue();
});

test('update sekolah yang tidak memiliki admin akan membuat admin jika password diisi', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    $school = School::factory()->create([
        'npsn' => '44444444',
        'name' => 'Sekolah Tanpa Admin'
    ]);

    SchoolSetting::factory()->create(['school_id' => $school->id]);

    expect($school->account)->toBeNull();

    actingAs($superadmin);

    Livewire::test(SchoolForm::class, ['school' => $school])
        ->set('npsn', '44444444')
        ->set('name', 'Sekolah Tanpa Admin')
        ->set('address', 'Jl. Baru')
        ->set('phone_number', '123')
        ->set('kop_pusat', 'Kop')
        ->set('kop_provinsi', 'Prov')
        ->set('password', 'newadmin123')
        ->call('save')
        ->assertRedirect(route('schools.index'));

    $admin = $school->fresh()->account;
    expect($admin)->not->toBeNull()
        ->and($admin->username)->toBe('44444444')
        ->and(Hash::check('newadmin123', $admin->password))->toBeTrue();
});

test('update sekolah tanpa admin dan password kosong tidak membuat admin', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    $school = School::factory()->create([
        'npsn' => '55555555',
        'name' => 'Sekolah Tanpa Admin 2'
    ]);

    SchoolSetting::factory()->create(['school_id' => $school->id]);

    expect($school->account)->toBeNull();

    actingAs($superadmin);

    Livewire::test(SchoolForm::class, ['school' => $school])
        ->set('npsn', '55555555')
        ->set('name', 'Sekolah Tanpa Admin 2')
        ->set('address', 'Jl. Baru')
        ->set('phone_number', '123')
        ->set('kop_pusat', 'Kop')
        ->set('kop_provinsi', 'Prov')
        ->set('password', '')
        ->call('save')
        ->assertRedirect(route('schools.index'));

    expect($school->fresh()->account)->toBeNull();
});

test('setiap sekolah hanya memiliki satu admin', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    $school = School::factory()->create(['npsn' => '66666666']);
    SchoolSetting::factory()->create(['school_id' => $school->id]);

    User::factory()->create([
        'school_id' => $school->id,
        'role' => 'school',
        'username' => '66666666'
    ]);

    $adminCount = User::where('school_id', $school->id)->where('role', 'school')->count();
    expect($adminCount)->toBe(1);

    actingAs($superadmin);
    Livewire::test(SchoolForm::class, ['school' => $school])
        ->set('npsn', '66666666')
        ->set('name', 'Sekolah 6')
        ->set('address', 'Jl. 6')
        ->set('phone_number', '123')
        ->set('kop_pusat', 'Kop')
        ->set('kop_provinsi', 'Prov')
        ->set('password', '')
        ->call('save');

    $adminCountAfter = User::where('school_id', $school->id)->where('role', 'school')->count();
    expect($adminCountAfter)->toBe(1);
});
test('pengguna bukan superadmin tidak dapat membuka form sekolah', function () {
    $nonSuperadmin = User::factory()->create(['role' => 'admin']);

    actingAs($nonSuperadmin)
        ->get(route('schools.create'))
        ->assertStatus(403);
});

test('komponen form menolak non-superadmin meski dipanggil langsung', function () {
    $nonSuperadmin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($nonSuperadmin)
        ->test(SchoolForm::class)
        ->assertStatus(403);
});

test('superadmin dapat menyimpan kepala sekolah beserta pembatasan sumber dana', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    $bos = FundingSource::factory()->create(['name' => 'BOS Reguler']);
    $bop = FundingSource::factory()->create(['name' => 'BOP Khusus']);

    actingAs($superadmin);

    Livewire::test(SchoolForm::class)
        ->set('npsn', '99998888')
        ->set('name', 'SMA Kepala Terang')
        ->set('address', 'Jl. Kenanga No. 9')
        ->set('phone_number', '0263111222')
        ->set('principal_name', 'Dra. Siti Aminah, M.Pd.')
        ->set('principal_nip', '197001011994031005')
        ->set('funding_source_ids', [$bos->id, $bop->id])
        ->set('status', 'active')
        ->set('kop_pusat', 'Kop Pusat')
        ->set('kop_provinsi', 'Kop Provinsi')
        ->set('password', 'rahasia123')
        ->call('save')
        ->assertRedirect(route('schools.index'));

    $school = School::where('npsn', '99998888')->first();

    expect($school->principal_name)->toBe('Dra. Siti Aminah, M.Pd.')
        ->and($school->principal_nip)->toBe('197001011994031005')
        ->and($school->fundingSources()->pluck('funding_sources.id')->sort()->values()->all())
        ->toBe(collect([$bos->id, $bop->id])->sort()->values()->all());
});

test('update sekolah menyinkronkan sumber dana lama dan dapat dikosongkan', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    $school = School::factory()->create(['npsn' => '77777777']);
    SchoolSetting::factory()->create(['school_id' => $school->id]);
    $fsA = FundingSource::factory()->create();
    $fsB = FundingSource::factory()->create();
    $school->fundingSources()->attach([$fsA->id, $fsB->id]);

    actingAs($superadmin);

    Livewire::test(SchoolForm::class, ['school' => $school])
        ->assertSet('funding_source_ids', [$fsA->id, $fsB->id])
        ->set('npsn', '77777777')
        ->set('name', 'SMA Sinkron')
        ->set('address', 'Jl. Sinkron')
        ->set('phone_number', '123')
        ->set('kop_pusat', 'Kop')
        ->set('kop_provinsi', 'Prov')
        ->set('funding_source_ids', [])
        ->set('password', '')
        ->call('save')
        ->assertRedirect(route('schools.index'));

    expect($school->fundingSources()->count())->toBe(0);
});

test('sumber dana tidak valid memicu error validasi', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    FundingSource::factory()->create();

    actingAs($superadmin);

    Livewire::test(SchoolForm::class)
        ->set('npsn', '12121212')
        ->set('name', 'SMA Validasi')
        ->set('address', 'Jl. Validasi')
        ->set('phone_number', '123')
        ->set('funding_source_ids', [999999])
        ->set('status', 'active')
        ->set('kop_pusat', 'Kop')
        ->set('kop_provinsi', 'Prov')
        ->set('password', 'rahasia123')
        ->call('save')
        ->assertHasErrors(['funding_source_ids.0' => 'exists']);
});

test('mengedit sekolah tidak mengubah dokumen final yang sudah digenerate', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    $school = School::factory()->create(['npsn' => '88881111', 'name' => 'SMA Dokumen Tetap']);
    SchoolSetting::factory()->create(['school_id' => $school->id]);
    $request = ProcurementRequest::factory()->create(['school_id' => $school->id]);
    $document = GeneratedDocument::create([
        'procurement_request_id' => $request->id,
        'document_type' => 'purchase_order',
        'file_path' => 'documents/po-88881111.pdf',
        'download_token' => 'token-abc-123',
        'generated_at' => now(),
    ]);

    actingAs($superadmin);

    Livewire::test(SchoolForm::class, ['school' => $school])
        ->set('npsn', '88881111')
        ->set('name', 'SMA Nama Baru Setelah PO Terbit')
        ->set('address', 'Jl. Diubah No. 1')
        ->set('phone_number', '0263999999')
        ->set('kop_pusat', 'Kop Baru Setelah PO')
        ->set('kop_provinsi', 'Prov Baru')
        ->set('password', '')
        ->call('save')
        ->assertRedirect(route('schools.index'));

    $document->refresh();

    expect($document->file_path)->toBe('documents/po-88881111.pdf')
        ->and($document->download_token)->toBe('token-abc-123');
});
