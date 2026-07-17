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
        Schema::create('procurement_negotiations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_request_item_id')->constrained('procurement_request_items')->cascadeOnDelete();
            $table->unsignedInteger('round_number')->default(1);
            $table->string('offered_by', 20);
            $table->foreignId('user_id')->constrained('users');
            $table->decimal('offered_price', 15, 2);
            $table->string('status', 20)->default('pending')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique([
                'procurement_request_item_id',
                'round_number'
            ], 'negotiation_round_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurement_negotiations');
    }
};
