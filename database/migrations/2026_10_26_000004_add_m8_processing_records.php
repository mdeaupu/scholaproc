<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procurement_requests', function (Blueprint $table): void {
            $table->boolean('tax_configuration_validated')->default(false);
            $table->text('tax_configuration_basis')->nullable();
            $table->foreignId('tax_configuration_validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('tax_configuration_validated_at')->nullable();
            $table->json('price_snapshot')->nullable();
            $table->json('tax_snapshot')->nullable();
            $table->json('subsidiary_snapshot')->nullable();
        });

        Schema::table('procurement_requests', function (Blueprint $table): void {
            $table->decimal('ppn_rate', 5, 2)->nullable()->default(null)->change();
            $table->decimal('pph_22_rate', 5, 2)->nullable()->default(null)->change();
            $table->decimal('pph_23_rate', 5, 2)->nullable()->default(null)->change();
            $table->boolean('is_taxable')->nullable()->default(null)->change();
        });

        Schema::table('procurement_negotiations', function (Blueprint $table): void {
            $table->foreignId('school_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('school_approved_at')->nullable();
            $table->foreignId('provider_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('provider_approved_at')->nullable();
        });

        Schema::table('procurement_documents', function (Blueprint $table): void {
            $table->json('document_snapshot')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::create('procurement_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('procurement_request_id')->constrained()->cascadeOnDelete();
            $table->date('delivered_at');
            $table->foreignId('recorded_by')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('procurement_delivery_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('procurement_delivery_id')->constrained()->cascadeOnDelete();
            $table->foreignId('procurement_request_item_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->timestamps();
            $table->unique(['procurement_delivery_id', 'procurement_request_item_id'], 'delivery_item_unique');
        });

        Schema::create('procurement_receipts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('procurement_request_id')->constrained()->cascadeOnDelete();
            $table->date('received_at');
            $table->foreignId('recorded_by')->constrained('users');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('procurement_receipt_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('procurement_receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('procurement_request_item_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->string('condition', 30);
            $table->text('issue_notes')->nullable();
            $table->string('follow_up_status', 20)->default('none');
            $table->timestamps();
            $table->unique(['procurement_receipt_id', 'procurement_request_item_id'], 'receipt_item_unique');
        });

        Schema::create('procurement_invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('procurement_request_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('invoice_number', 100)->unique();
            $table->date('invoice_date');
            $table->decimal('subtotal', 15, 2);
            $table->decimal('tax_amount', 15, 2);
            $table->decimal('total_amount', 15, 2);
            $table->json('tax_snapshot');
            $table->json('price_snapshot');
            $table->json('subsidiary_snapshot');
            $table->foreignId('processed_by')->constrained('users');
            $table->timestamp('processed_at');
            $table->timestamps();
        });

        Schema::create('procurement_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('procurement_invoice_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('method', 50);
            $table->string('reference', 100)->nullable();
            $table->date('paid_at');
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_payments');
        Schema::dropIfExists('procurement_invoices');
        Schema::dropIfExists('procurement_receipt_items');
        Schema::dropIfExists('procurement_receipts');
        Schema::dropIfExists('procurement_delivery_items');
        Schema::dropIfExists('procurement_deliveries');

        Schema::table('procurement_documents', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('issued_by');
            $table->dropColumn(['document_snapshot', 'version']);
        });

        Schema::table('procurement_negotiations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('school_approved_by');
            $table->dropConstrainedForeignId('provider_approved_by');
            $table->dropColumn(['school_approved_at', 'provider_approved_at']);
        });

        Schema::table('procurement_requests', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('tax_configuration_validated_by');
            $table->dropColumn([
                'tax_configuration_validated',
                'tax_configuration_basis',
                'tax_configuration_validated_at',
                'price_snapshot',
                'tax_snapshot',
                'subsidiary_snapshot',
            ]);
        });
    }
};
