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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('pic_name');
            $table->string('email');
            $table->string('phone', 20);
            $table->text('address');
            $table->string('npwp', 30);
            $table->string('nib', 50);
            $table->string('director_name');
            $table->string('director_nik', 16);
            $table->string('director_npwp', 30)->nullable();
            $table->string('director_phone', 20)->nullable();
            $table->string('commissioner_name')->nullable();
            $table->string('commissioner_nik', 16)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
