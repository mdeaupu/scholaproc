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
            $table->foreignId('school_id')->constrained();
            $table->foreignId('supplier_id')->nullable()->constrained();
            $table->enum('status', ['draft', 'submitted', 'verified', 'supplier_assigned', 'items_prepared', 'completed', 'rejected'])->index();
            $table->string('package_category');
            $table->year('budget_year')->index();
            $table->string('funding_source', 100);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('work_duration_text', 50)->nullable();
            $table->boolean('is_taxable')->default(true);
            $table->decimal('ppn_rate', 5, 2)->default(11.00);
            $table->decimal('pph_22_rate', 5, 2)->default(0.00);
            $table->decimal('pph_23_rate', 5, 2)->default(0.00);
            $table->text('cv_notes')->nullable();
            $table->timestamp('requested_at')->nullable();
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
