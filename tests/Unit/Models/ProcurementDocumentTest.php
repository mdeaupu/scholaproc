<?php

use App\Models\ProcurementDocument;
use App\Models\ProcurementRequest;
use App\Models\Subsidiary;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('isComplete mengecek status kelengkapan dokumen', function () {
    $document = ProcurementDocument::factory()->create([
        'document_number' => null,
        'document_date' => null,
    ]);

    expect($document->isComplete())->toBeFalse();

    $document->update([
        'document_number' => 'DOC-2026/06/1234',
        'document_date' => now(),
    ]);

    expect($document->isComplete())->toBeTrue();
});

test('generateNumber men-generate nomor dokumen otomatis', function () {
    $subsidiary = Subsidiary::factory()->create(['name' => 'PT Anak Perusahaan']);
    $request = ProcurementRequest::factory()->for($subsidiary, 'subsidiary')->create();

    $document = ProcurementDocument::factory()->create([
        'procurement_request_id' => $request->id,
        'document_type' => 'bast',
    ]);

    $generatedNumber = $document->generateNumber('001');

    expect($generatedNumber)->not->toBeEmpty();

    $currentYear = date('Y');
    expect($generatedNumber)
        ->toContain('pt-anak-perusahaan')
        ->toContain($currentYear)
        ->toContain('BAST')
        ->toContain('001');
});

describe('ProcurementDocument subsidiary snapshot', function () {
    it('requires an assigned subsidiary before issuing an official document', function () {
        $request = ProcurementRequest::factory()->create();
        $document = ProcurementDocument::create([
            'procurement_request_id' => $request->id,
            'document_type' => 'purchase_order',
        ]);

        expect(fn () => $document->issue())
            ->toThrow(LogicException::class, 'Dokumen resmi tidak dapat diterbitkan sebelum Anak Perusahaan ditetapkan.');
    });

    it('captures subsidiary identity when issued and preserves the snapshot', function () {
        Storage::fake('public');
        $letterheadPath = UploadedFile::fake()->image('kop.png')->store('subsidiary-letterheads', 'public');
        $subsidiary = Subsidiary::factory()->create([
            'name' => 'PT Identitas Awal',
            'npwp' => '123456789012345',
            'director_name' => 'Direktur Awal',
            'address' => 'Alamat awal',
            'phone' => '02112345678',
            'email' => 'awal@example.test',
            'letterhead_path' => $letterheadPath,
        ]);
        $request = ProcurementRequest::factory()->create(['status' => ProcurementRequest::STATUS_VERIFIED]);
        $admin = User::factory()->admin()->create();
        $request->assignSubsidiary($subsidiary, $admin);
        $document = ProcurementDocument::create([
            'procurement_request_id' => $request->id,
            'document_type' => 'planning',
            'issued_by' => $admin->id,
        ]);

        $document->issue();
        $snapshot = $document->fresh()->subsidiary_snapshot;

        expect($document->fresh()->subsidiary_id)->toBe($subsidiary->id)
            ->and($snapshot['name'])->toBe('PT Identitas Awal')
            ->and($snapshot['npwp'])->toBe('123456789012345')
            ->and($snapshot['director_name'])->toBe('Direktur Awal')
            ->and($snapshot['address'])->toBe('Alamat awal')
            ->and($snapshot['phone'])->toBe('02112345678')
            ->and($snapshot['email'])->toBe('awal@example.test')
            ->and($snapshot['letterhead_path'])->toBe($letterheadPath)
            ->and($snapshot['letterhead_url'])->toBe(Storage::disk('public')->url($letterheadPath))
            ->and($snapshot['letterhead_layout'])->toBe('subsidiary-letterhead');

        $letterhead = view('components.documents.subsidiary-letterhead', $snapshot)->render();

        expect($letterhead)
            ->toContain('PT Identitas Awal')
            ->toContain('NPWP 123456789012345')
            ->toContain('Alamat awal');

        $subsidiary->update([
            'name' => 'PT Identitas Baru',
            'npwp' => '987654321098765',
            'director_name' => 'Direktur Baru',
            'address' => 'Alamat baru',
            'phone' => '02187654321',
            'email' => 'baru@example.test',
        ]);

        expect($document->fresh()->subsidiary_snapshot)->toBe($snapshot);
    });

    it('does not allow changing an issued document snapshot', function () {
        $subsidiary = Subsidiary::factory()->create();
        $request = ProcurementRequest::factory()->create(['status' => ProcurementRequest::STATUS_VERIFIED]);
        $admin = User::factory()->admin()->create();
        $request->assignSubsidiary($subsidiary, $admin);
        $document = ProcurementDocument::create([
            'procurement_request_id' => $request->id,
            'document_type' => 'planning',
            'issued_by' => $admin->id,
        ]);
        $document->issue();

        expect(function () use ($document): void {
            $document->subsidiary_snapshot = ['name' => 'Changed'];
            $document->save();
        })
            ->toThrow(LogicException::class);
    });

    it('prevents assigning an inactive subsidiary to a new transaction', function () {
        $inactiveSubsidiary = Subsidiary::factory()->create(['status' => 'inactive']);

        expect(fn () => ProcurementRequest::factory()
            ->for($inactiveSubsidiary, 'subsidiary')
            ->create())
            ->toThrow(LogicException::class);
    });

    it('stores who assigned a subsidiary and when', function () {
        $request = ProcurementRequest::factory()->create(['status' => ProcurementRequest::STATUS_VERIFIED]);
        $subsidiary = Subsidiary::factory()->create();
        $admin = User::factory()->admin()->create();

        $request->assignSubsidiary($subsidiary, $admin);

        expect($request->fresh()->subsidiary_id)->toBe($subsidiary->id)
            ->and($request->fresh()->subsidiary_assigned_by)->toBe($admin->id)
            ->and($request->fresh()->subsidiary_assigned_at)->not->toBeNull();
    });

    it('only allows central administrators to assign a subsidiary', function () {
        $request = ProcurementRequest::factory()->create();
        $subsidiary = Subsidiary::factory()->create();
        $schoolUser = User::factory()->school()->create();

        expect(fn () => $request->assignSubsidiary($subsidiary, $schoolUser))
            ->toThrow(AuthorizationException::class);
    });

    it('locks the transaction subsidiary after official issuance', function () {
        $original = Subsidiary::factory()->create();
        $replacement = Subsidiary::factory()->create();
        $request = ProcurementRequest::factory()->create(['status' => ProcurementRequest::STATUS_VERIFIED]);
        $admin = User::factory()->admin()->create();
        $request->assignSubsidiary($original, $admin);
        $document = ProcurementDocument::create([
            'procurement_request_id' => $request->id,
            'document_type' => 'planning',
            'issued_by' => $admin->id,
        ]);
        $document->issue();

        expect(fn () => $request->assignSubsidiary($replacement, $admin))
            ->toThrow(LogicException::class);
    });
});
