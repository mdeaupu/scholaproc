<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procurement_verification_files', function (Blueprint $table): void {
            $table->foreignId('procurement_receipt_id')
                ->nullable()
                ->after('procurement_request_id')
                ->constrained('procurement_receipts')
                ->nullOnDelete();
        });

        Schema::table('procurement_receipts', function (Blueprint $table): void {
            $table->timestamp('voided_at')->nullable()->after('verified_at');
            $table->foreignId('voided_by')
                ->nullable()
                ->after('voided_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->text('void_reason')->nullable()->after('voided_by');
        });
    }

    public function down(): void
    {
        Schema::table('procurement_receipts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('voided_by');
            $table->dropColumn(['voided_at', 'void_reason']);
        });

        Schema::table('procurement_verification_files', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('procurement_receipt_id');
        });
    }
};
