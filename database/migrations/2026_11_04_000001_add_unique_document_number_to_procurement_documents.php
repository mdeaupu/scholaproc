<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('procurement_documents')
            ->select('document_number')
            ->whereNotNull('document_number')
            ->groupBy('document_number')
            ->havingRaw('COUNT(*) > 1')
            ->limit(10)
            ->pluck('document_number');

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException(
                'Cannot add the unique procurement document number index; resolve duplicate numbers first: '
                .$duplicates->implode(', ')
            );
        }

        Schema::table('procurement_documents', function (Blueprint $table): void {
            $table->unique('document_number', 'procurement_documents_document_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('procurement_documents', function (Blueprint $table): void {
            $table->dropUnique('procurement_documents_document_number_unique');
        });
    }
};
