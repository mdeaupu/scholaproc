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
        Schema::create('generated_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_request_id')->constrained();
            $table->enum('document_type', ['cover', 'planning', 'negotiation', 'purchase_order', 'inspection', 'bast', 'invoice', 'receipt']);
            $table->string('file_path');
            $table->string('download_token', 64)->unique()->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generated_documents');
    }
};
