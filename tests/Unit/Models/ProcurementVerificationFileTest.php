<?php

use App\Models\ProcurementRequest;
use App\Models\ProcurementVerificationFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->procurement = ProcurementRequest::factory()->create();
    $this->user = User::factory()->create(['role' => 'school']);
});

// ─── Constants ───────────────────────────────────────────────

test('has correct type constants', function () {
    expect(ProcurementVerificationFile::TYPE_PHOTO)->toBe('photo')
        ->and(ProcurementVerificationFile::TYPE_SIGNED_DOCUMENT)->toBe('signed_document');
});

// ─── isPhoto() ───────────────────────────────────────────────

test('isPhoto returns true for photo type', function () {
    $file = ProcurementVerificationFile::create([
        'procurement_request_id' => $this->procurement->id,
        'file_type' => ProcurementVerificationFile::TYPE_PHOTO,
        'file_path' => 'verification_files/1/test.jpg',
        'uploaded_by' => $this->user->id,
        'uploaded_at' => now(),
    ]);

    expect($file->isPhoto())->toBeTrue()
        ->and($file->isSignedDocument())->toBeFalse();
});

test('isSignedDocument returns true for signed_document type', function () {
    $file = ProcurementVerificationFile::create([
        'procurement_request_id' => $this->procurement->id,
        'file_type' => ProcurementVerificationFile::TYPE_SIGNED_DOCUMENT,
        'file_path' => 'verification_files/1/bast.pdf',
        'uploaded_by' => $this->user->id,
        'uploaded_at' => now(),
    ]);

    expect($file->isSignedDocument())->toBeTrue()
        ->and($file->isPhoto())->toBeFalse();
});

// ─── Relationships ───────────────────────────────────────────

test('belongs to procurement request', function () {
    $file = ProcurementVerificationFile::create([
        'procurement_request_id' => $this->procurement->id,
        'file_type' => ProcurementVerificationFile::TYPE_PHOTO,
        'file_path' => 'verification_files/1/test.jpg',
        'uploaded_by' => $this->user->id,
        'uploaded_at' => now(),
    ]);

    expect($file->procurementRequest)->not->toBeNull()
        ->and($file->procurementRequest->id)->toBe($this->procurement->id);
});

test('belongs to uploader', function () {
    $file = ProcurementVerificationFile::create([
        'procurement_request_id' => $this->procurement->id,
        'file_type' => ProcurementVerificationFile::TYPE_PHOTO,
        'file_path' => 'verification_files/1/test.jpg',
        'uploaded_by' => $this->user->id,
        'uploaded_at' => now(),
    ]);

    expect($file->uploader)->not->toBeNull()
        ->and($file->uploader->id)->toBe($this->user->id);
});

// ─── Fillable & Casts ────────────────────────────────────────

test('has correct fillable attributes', function () {
    $file = new ProcurementVerificationFile();

    expect($file->getFillable())->toBe([
        'procurement_request_id',
        'file_type',
        'file_path',
        'uploaded_by',
        'notes',
        'uploaded_at',
    ]);
});

test('has correct casts for uploaded_at', function () {
    $file = ProcurementVerificationFile::create([
        'procurement_request_id' => $this->procurement->id,
        'file_type' => ProcurementVerificationFile::TYPE_PHOTO,
        'file_path' => 'verification_files/1/test.jpg',
        'uploaded_by' => $this->user->id,
        'uploaded_at' => now(),
    ]);

    expect($file->uploaded_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

test('timestamps are disabled', function () {
    $file = new ProcurementVerificationFile();

    expect($file->timestamps)->toBeFalse();
});
