<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $supplierIds = DB::table('suppliers')->orderBy('id')->pluck('id')->all();
        $unmappedSupplierIds = array_diff($supplierIds, [1]);
        $unmappedRequestSupplierIds = DB::table('procurement_requests')
            ->whereNotNull('supplier_id')
            ->distinct()
            ->pluck('supplier_id')
            ->diff([1])
            ->all();
        $unmappedLegalDocumentSupplierIds = DB::table('supplier_legal_documents')
            ->whereNotIn('supplier_id', [1])
            ->distinct()
            ->pluck('supplier_id')
            ->all();

        if ($unmappedSupplierIds || $unmappedRequestSupplierIds || $unmappedLegalDocumentSupplierIds) {
            throw new RuntimeException(
                'Migrasi M4 dihentikan: terdapat ID Supplier selain ID 1 yang belum memiliki pemetaan Anak Perusahaan yang disetujui.'
            );
        }

        $legacySubsidiary = DB::table('suppliers')->where('id', 1)->first();

        if ($legacySubsidiary && (
            $legacySubsidiary->deleted_at !== null
            || trim((string) $legacySubsidiary->company_name) === ''
            || trim((string) $legacySubsidiary->npwp) === ''
            || trim((string) $legacySubsidiary->director_name) === ''
        )) {
            throw new RuntimeException(
                'Migrasi M4 dihentikan: data Supplier ID 1 tidak memenuhi identitas wajib atau berstatus terhapus.'
            );
        }

        Schema::create('subsidiaries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('npwp', 30)->unique();
            $table->string('director_name');
            $table->string('pic_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('nib', 50)->nullable();
            $table->string('director_nik', 16)->nullable();
            $table->string('director_npwp', 30)->nullable();
            $table->string('director_phone', 20)->nullable();
            $table->text('director_address')->nullable();
            $table->string('commissioner_name')->nullable();
            $table->string('commissioner_nik', 16)->nullable();
            $table->text('commissioner_address')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
        });

        Schema::create('subsidiary_legal_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subsidiary_id')->constrained()->restrictOnDelete();
            $table->string('document_type', 50);
            $table->string('document_number', 100)->nullable();
            $table->date('document_date')->nullable();
            $table->string('notary_name')->nullable();
            $table->string('issuer')->nullable();
            $table->date('valid_until')->nullable();
            $table->timestamps();
        });

        Schema::table('procurement_requests', function (Blueprint $table) {
            $table->foreignId('subsidiary_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('subsidiary_assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('subsidiary_assigned_at')->nullable();
        });

        Schema::table('procurement_documents', function (Blueprint $table) {
            $table->foreignId('subsidiary_id')->nullable()->constrained()->restrictOnDelete();
            $table->json('subsidiary_snapshot')->nullable();
            $table->timestamp('issued_at')->nullable();
        });

        if ($legacySubsidiary) {
            DB::table('subsidiaries')->insert([
                'id' => 1,
                'name' => $legacySubsidiary->company_name,
                'npwp' => $legacySubsidiary->npwp,
                'director_name' => $legacySubsidiary->director_name,
                'pic_name' => $legacySubsidiary->pic_name,
                'email' => $legacySubsidiary->email,
                'phone' => $legacySubsidiary->phone,
                'address' => $legacySubsidiary->address,
                'nib' => $legacySubsidiary->nib,
                'director_nik' => $legacySubsidiary->director_nik,
                'director_npwp' => $legacySubsidiary->director_npwp,
                'director_phone' => $legacySubsidiary->director_phone,
                'director_address' => $legacySubsidiary->director_address,
                'commissioner_name' => $legacySubsidiary->commissioner_name,
                'commissioner_nik' => $legacySubsidiary->commissioner_nik,
                'commissioner_address' => $legacySubsidiary->commissioner_address,
                'status' => 'active',
                'created_at' => $legacySubsidiary->created_at,
                'updated_at' => $legacySubsidiary->updated_at,
            ]);

            $legalDocuments = DB::table('supplier_legal_documents')
                ->where('supplier_id', 1)
                ->get();

            foreach ($legalDocuments as $document) {
                DB::table('subsidiary_legal_documents')->insert([
                    'id' => $document->id,
                    'subsidiary_id' => 1,
                    'document_type' => $document->document_type,
                    'document_number' => $document->document_number,
                    'document_date' => $document->document_date,
                    'notary_name' => $document->notary_name,
                    'issuer' => $document->issuer,
                    'valid_until' => $document->valid_until
                        ? Carbon::parse($document->valid_until)->toDateString()
                        : null,
                    'created_at' => $document->created_at,
                    'updated_at' => $document->updated_at,
                ]);
            }

            DB::table('procurement_requests')
                ->where('supplier_id', 1)
                ->update(['subsidiary_id' => 1]);
        }
    }

    public function down(): void
    {
        $subsidiaries = DB::table('subsidiaries')->get();
        $legacySubsidiary = DB::table('suppliers')->where('id', 1)->first();

        if (
            DB::table('subsidiary_legal_documents')->exists()
            || DB::table('procurement_requests')->whereNotNull('subsidiary_id')->exists()
            || DB::table('procurement_documents')
                ->whereNotNull('subsidiary_id')
                ->orWhereNotNull('subsidiary_snapshot')
                ->orWhereNotNull('issued_at')
                ->exists()
            || $subsidiaries->count() !== ($legacySubsidiary ? 1 : 0)
        ) {
            throw new RuntimeException(
                'Rollback M4 ditolak: data Anak Perusahaan baru telah digunakan atau berubah dan tidak aman untuk dihapus.'
            );
        }

        if ($legacySubsidiary) {
            $subsidiary = $subsidiaries->first();
            $fieldMap = [
                'name' => 'company_name',
                'npwp' => 'npwp',
                'director_name' => 'director_name',
                'pic_name' => 'pic_name',
                'email' => 'email',
                'phone' => 'phone',
                'address' => 'address',
                'nib' => 'nib',
                'director_nik' => 'director_nik',
                'director_npwp' => 'director_npwp',
                'director_phone' => 'director_phone',
                'director_address' => 'director_address',
                'commissioner_name' => 'commissioner_name',
                'commissioner_nik' => 'commissioner_nik',
                'commissioner_address' => 'commissioner_address',
            ];
            $mappedFieldsMatch = (int) $subsidiary->id === (int) $legacySubsidiary->id
                && $subsidiary->status === 'active';

            foreach ($fieldMap as $subsidiaryField => $legacyField) {
                $mappedFieldsMatch = $mappedFieldsMatch
                    && (string) ($subsidiary->{$subsidiaryField} ?? '') === (string) ($legacySubsidiary->{$legacyField} ?? '');
            }

            if (! $mappedFieldsMatch) {
                throw new RuntimeException(
                    'Rollback M4 ditolak: data Anak Perusahaan ID 1 telah berubah dari data Supplier asal.'
                );
            }
        }

        Schema::table('procurement_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subsidiary_assigned_by');
            $table->dropColumn('subsidiary_assigned_at');
            $table->dropConstrainedForeignId('subsidiary_id');
        });

        Schema::table('procurement_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subsidiary_id');
            $table->dropColumn(['subsidiary_snapshot', 'issued_at']);
        });

        Schema::dropIfExists('subsidiary_legal_documents');
        Schema::dropIfExists('subsidiaries');
    }
};
