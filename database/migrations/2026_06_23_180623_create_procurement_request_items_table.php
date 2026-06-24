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
        Schema::create('procurement_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_request_id')->constrained()->cascadeOnDelete();
            $table->integer('line_number')->default(0)->index();
            $table->string('item_name');
            $table->text('specification');
            $table->string('unit', 50);
            $table->integer('quantity');
            $table->decimal('estimated_price', 15, 2);
            $table->decimal('official_price', 15, 2)->nullable();
            $table->boolean('is_pph')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurement_request_items');
    }
};
