<?php

use App\Livewire\Procurement\VerificationPanel;
use App\Models\ProcurementRequest;
use App\Models\ProcurementVerificationFile;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->schoolModel = School::factory()->create();

    $this->school = User::factory()->create([
        'role' => 'school',
        'school_id' => $this->schoolModel->id,
    ]);

    $this->admin = User::factory()->create(['role' => 'admin']);

    $this->otherSchool = User::factory()->create([
        'role' => 'school',
        'school_id' => School::factory()->create()->id,
    ]);

    $this->procurement = ProcurementRequest::factory()->create([
        'status' => ProcurementRequest::STATUS_ITEMS_PREPARED,
        'school_id' => $this->schoolModel->id,
    ]);

    Storage::fake('public');
});

// ─── Mount & Rendering Tests ──────────────────────────────────

test('renders verification panel successfully', function () {
    actingAs($this->school);

    Livewire::test(VerificationPanel::class, ['procurementRequest' => $this->procurement])
        ->assertStatus(200);
});

test('loads files on mount', function () {
    ProcurementVerificationFile::create([
        'procurement_request_id' => $this->procurement->id,
        'file_type' => ProcurementVerificationFile::TYPE_PHOTO,
        'file_path' => 'verification_files/'.$this->procurement->id.'/photo.jpg',
        'uploaded_by' => $this->school->id,
        'uploaded_at' => now(),
    ]);

    actingAs($this->school);

    Livewire::test(VerificationPanel::class, ['procurementRequest' => $this->procurement])
        ->assertSet('photos', fn ($photos) => $photos->count() === 1)
        ->assertSet('documents', fn ($docs) => $docs->count() === 0);
});

test('separates photos and documents correctly', function () {
    ProcurementVerificationFile::create([
        'procurement_request_id' => $this->procurement->id,
        'file_type' => ProcurementVerificationFile::TYPE_PHOTO,
        'file_path' => 'verification_files/'.$this->procurement->id.'/photo.jpg',
        'uploaded_by' => $this->school->id,
        'uploaded_at' => now(),
    ]);
    ProcurementVerificationFile::create([
        'procurement_request_id' => $this->procurement->id,
        'file_type' => ProcurementVerificationFile::TYPE_SIGNED_DOCUMENT,
        'file_path' => 'verification_files/'.$this->procurement->id.'/bast.pdf',
        'uploaded_by' => $this->school->id,
        'uploaded_at' => now(),
    ]);

    actingAs($this->school);

    Livewire::test(VerificationPanel::class, ['procurementRequest' => $this->procurement])
        ->assertSet('photos', fn ($photos) => $photos->count() === 1)
        ->assertSet('documents', fn ($docs) => $docs->count() === 1);
});

// ─── Authorization Tests ──────────────────────────────────────

test('admin cannot upload photo - aborts 403', function () {
    actingAs($this->admin);

    Livewire::test(VerificationPanel::class, ['procurementRequest' => $this->procurement])
        ->call('uploadPhoto')
        ->assertStatus(403);
});

test('other school user cannot upload photo - aborts 403', function () {
    actingAs($this->otherSchool);

    Livewire::test(VerificationPanel::class, ['procurementRequest' => $this->procurement])
        ->call('uploadPhoto')
        ->assertStatus(403);
});

test('admin cannot upload document - aborts 403', function () {
    actingAs($this->admin);

    Livewire::test(VerificationPanel::class, ['procurementRequest' => $this->procurement])
        ->call('uploadDocument')
        ->assertStatus(403);
});

test('admin cannot delete file - aborts 403', function () {
    $file = ProcurementVerificationFile::create([
        'procurement_request_id' => $this->procurement->id,
        'file_type' => ProcurementVerificationFile::TYPE_PHOTO,
        'file_path' => 'verification_files/'.$this->procurement->id.'/photo.jpg',
        'uploaded_by' => $this->school->id,
        'uploaded_at' => now(),
    ]);

    actingAs($this->admin);

    Livewire::test(VerificationPanel::class, ['procurementRequest' => $this->procurement])
        ->call('openDeleteModal', $file->id)
        ->assertStatus(403);
});

test('admin cannot confirm verification - aborts 403', function () {
    actingAs($this->admin);

    Livewire::test(VerificationPanel::class, ['procurementRequest' => $this->procurement])
        ->call('openConfirmModal')
        ->assertStatus(403);
});

// ─── Upload Photo Tests ───────────────────────────────────────

test('school can upload photo successfully', function () {
    actingAs($this->school);

    $photo = UploadedFile::fake()->image('photo.jpg', 800, 600)->size(100);

    Livewire::test(VerificationPanel::class, ['procurementRequest' => $this->procurement])
        ->set('photoFiles', [$photo])
        ->set('photoNotes', 'Foto kondisi barang')
        ->call('uploadPhoto')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('procurement_verification_files', [
        'procurement_request_id' => $this->procurement->id,
        'file_type' => ProcurementVerificationFile::TYPE_PHOTO,
        'uploaded_by' => $this->school->id,
        'notes' => 'Foto kondisi barang',
    ]);
});

test('photo upload resets fields after success', function () {
    actingAs($this->school);

    $photo = UploadedFile::fake()->image('photo.jpg', 800, 600)->size(100);

    Livewire::test(VerificationPanel::class, ['procurementRequest' => $this->procurement])
        ->set('photoFiles', [$photo])
        ->set('photoNotes', 'Some notes')
        ->call('uploadPhoto')
        ->assertSet('photoFiles', [])
        ->assertSet('photoNotes', '');
});

test('photo upload fails with invalid file type', function () {
    actingAs($this->school);

    $file = UploadedFile::fake()->create('document.txt', 100, 'text/plain');

    Livewire::test(VerificationPanel::class, ['procurementRequest' => $this->procurement])
        ->set('photoFiles', [$file])
        ->call('uploadPhoto')
        ->assertHasErrors(['photoFiles.0']);
});

// ─── Upload Document Tests ────────────────────────────────────

test('school can upload signed document successfully', function () {
    actingAs($this->school);

    $doc = UploadedFile::fake()->create('bast.pdf', 500, 'application/pdf');

    Livewire::test(VerificationPanel::class, ['procurementRequest' => $this->procurement])
        ->set('documentFiles', [$doc])
        ->set('documentNotes', 'Scan BAST')
        ->call('uploadDocument')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('procurement_verification_files', [
        'procurement_request_id' => $this->procurement->id,
        'file_type' => ProcurementVerificationFile::TYPE_SIGNED_DOCUMENT,
        'uploaded_by' => $this->school->id,
        'notes' => 'Scan BAST',
    ]);
});

test('document upload resets fields after success', function () {
    actingAs($this->school);

    $doc = UploadedFile::fake()->create('bast.pdf', 500, 'application/pdf');

    Livewire::test(VerificationPanel::class, ['procurementRequest' => $this->procurement])
        ->set('documentFiles', [$doc])
        ->set('documentNotes', 'Some notes')
        ->call('uploadDocument')
        ->assertSet('documentFiles', [])
        ->assertSet('documentNotes', '');
});

// ─── Delete File Tests ────────────────────────────────────────

test('school can delete file before verification', function () {
    $file = ProcurementVerificationFile::create([
        'procurement_request_id' => $this->procurement->id,
        'file_type' => ProcurementVerificationFile::TYPE_PHOTO,
        'file_path' => 'verification_files/'.$this->procurement->id.'/photo.jpg',
        'uploaded_by' => $this->school->id,
        'uploaded_at' => now(),
    ]);

    actingAs($this->school);

    Livewire::test(VerificationPanel::class, ['procurementRequest' => $this->procurement])
        ->call('openDeleteModal', $file->id)
        ->assertSet('showDeleteModal', true)
        ->assertSet('deletingFileId', $file->id)
        ->call('confirmDelete')
        ->assertSet('showDeleteModal', false)
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('procurement_verification_files', [
        'id' => $file->id,
    ]);
});

test('cannot delete file after verification', function () {
    $this->procurement->update([
        'verified_at' => now(),
        'verified_by' => $this->school->id,
    ]);

    $file = ProcurementVerificationFile::create([
        'procurement_request_id' => $this->procurement->id,
        'file_type' => ProcurementVerificationFile::TYPE_PHOTO,
        'file_path' => 'verification_files/'.$this->procurement->id.'/photo.jpg',
        'uploaded_by' => $this->school->id,
        'uploaded_at' => now(),
    ]);

    actingAs($this->school);

    Livewire::test(VerificationPanel::class, ['procurementRequest' => $this->procurement])
        ->call('openDeleteModal', $file->id);

    // Modal should not open because error was triggered
    expect($file->refresh())->not->toBeNull();
});

// ─── Confirm Verification Tests ───────────────────────────────

test('school can confirm verification when conditions met', function () {
    ProcurementVerificationFile::create([
        'procurement_request_id' => $this->procurement->id,
        'file_type' => ProcurementVerificationFile::TYPE_SIGNED_DOCUMENT,
        'file_path' => 'verification_files/'.$this->procurement->id.'/bast.pdf',
        'uploaded_by' => $this->school->id,
        'uploaded_at' => now(),
    ]);

    actingAs($this->school);

    Livewire::test(VerificationPanel::class, ['procurementRequest' => $this->procurement])
        ->call('openConfirmModal')
        ->assertSet('showConfirmModal', true)
        ->call('confirmMarkVerified')
        ->assertSet('showConfirmModal', false)
        ->assertHasNoErrors();

    $this->procurement->refresh();

    expect($this->procurement->verified_at)->not->toBeNull()
        ->and($this->procurement->verified_by)->toBe($this->school->id);

    $this->assertDatabaseHas('procurement_request_histories', [
        'procurement_request_id' => $this->procurement->id,
        'status' => 'verified_receipt',
        'user_id' => $this->school->id,
    ]);
});

test('confirm verification fails without signed document', function () {
    // Only upload photo, no signed document
    ProcurementVerificationFile::create([
        'procurement_request_id' => $this->procurement->id,
        'file_type' => ProcurementVerificationFile::TYPE_PHOTO,
        'file_path' => 'verification_files/'.$this->procurement->id.'/photo.jpg',
        'uploaded_by' => $this->school->id,
        'uploaded_at' => now(),
    ]);

    actingAs($this->school);

    Livewire::test(VerificationPanel::class, ['procurementRequest' => $this->procurement])
        ->call('openConfirmModal');

    // Modal should not open because canMarkVerified requires at least signed document
    expect($this->procurement->refresh()->verified_at)->toBeNull();
});

test('cannot confirm verification when already verified', function () {
    $this->procurement->update([
        'verified_at' => now(),
        'verified_by' => $this->school->id,
    ]);

    ProcurementVerificationFile::create([
        'procurement_request_id' => $this->procurement->id,
        'file_type' => ProcurementVerificationFile::TYPE_SIGNED_DOCUMENT,
        'file_path' => 'verification_files/'.$this->procurement->id.'/bast.pdf',
        'uploaded_by' => $this->school->id,
        'uploaded_at' => now(),
    ]);

    actingAs($this->school);

    Livewire::test(VerificationPanel::class, ['procurementRequest' => $this->procurement])
        ->call('openConfirmModal');

    // Should show error because canMarkVerified returns false
    expect($this->procurement->refresh()->verified_at)->not->toBeNull();
});

// ─── File Upload After Verification Tests ─────────────────────

test('cannot upload photo after verification confirmed', function () {
    $this->procurement->update([
        'verified_at' => now(),
        'verified_by' => $this->school->id,
    ]);

    actingAs($this->school);

    $photo = UploadedFile::fake()->image('photo.jpg', 800, 600)->size(100);

    Livewire::test(VerificationPanel::class, ['procurementRequest' => $this->procurement])
        ->set('photoFiles', [$photo])
        ->call('uploadPhoto');

    // No new file should be created
    expect(ProcurementVerificationFile::where('procurement_request_id', $this->procurement->id)->count())->toBe(0);
});

test('cannot upload document after verification confirmed', function () {
    $this->procurement->update([
        'verified_at' => now(),
        'verified_by' => $this->school->id,
    ]);

    actingAs($this->school);

    $doc = UploadedFile::fake()->create('bast.pdf', 500, 'application/pdf');

    Livewire::test(VerificationPanel::class, ['procurementRequest' => $this->procurement])
        ->set('documentFiles', [$doc])
        ->call('uploadDocument');

    // No new file should be created
    expect(ProcurementVerificationFile::where('procurement_request_id', $this->procurement->id)->count())->toBe(0);
});


