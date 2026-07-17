<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('procurement_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('status', 30)->index();
            $table->foreignId('package_category_id')->constrained('package_categories');
            $table->foreignId('budget_year_id')->constrained('budget_years')->index();
            $table->foreignId('funding_source_id')->constrained('funding_sources');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('work_duration_text', 50)->nullable();
            $table->boolean('is_taxable')->default(true);
            $table->decimal('ppn_rate', 5, 2)->default(11.00);
            $table->decimal('pph_22_rate', 5, 2)->default(0.00);
            $table->decimal('pph_23_rate', 5, 2)->default(0.00);
            $table->decimal('subtotal', 15, 2)->nullable();
            $table->decimal('tax_amount', 15, 2)->nullable();
            $table->decimal('grand_total', 15, 2)->nullable();
            $table->timestamp('totals_locked_at')->nullable()->index();
            $table->text('cv_notes')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('verified_at')->nullable()->index();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurement_requests');
    }
};
