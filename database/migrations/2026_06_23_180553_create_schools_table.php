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
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('npsn', 8)->unique();
            $table->string('name');
            $table->text('address');
            $table->string('postal_code', 10)->nullable();
            $table->string('phone_number', 20);
            $table->string('email')->nullable();
            $table->enum('status', ['active', 'suspended'])->default('active');
            $table->timestamps();
            $table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
