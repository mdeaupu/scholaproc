<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('tax_type', 20)->index(); // ppn, pph22, pph23
            $table->string('name');
            $table->unsignedInteger('version')->default(1);
            $table->string('status', 20)->default('draft')->index(); // draft, pending, approved, active, rejected, expired
            $table->string('buyer_type', 20)->nullable(); // negeri, swasta, all
            $table->string('pkp_status', 20)->nullable(); // pkp, non_pkp, all
            $table->string('transaction_type', 50)->nullable();
            $table->string('item_type', 50)->nullable();
            $table->string('funding_source', 50)->nullable();
            $table->foreignId('subsidiary_id')->nullable()->constrained('subsidiaries')->nullOnDelete();
            $table->string('dpp_method', 50)->default('subtotal');
            $table->string('calculation_method', 20)->default('percentage');
            $table->decimal('rate', 8, 4)->nullable();
            $table->string('collector', 100)->nullable();
            $table->json('exemption')->nullable();
            $table->text('legal_basis')->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('change_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tax_type', 'status']);
            $table->index(['effective_from', 'effective_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rules');
    }
};
