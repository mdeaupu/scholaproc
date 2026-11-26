<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subsidiaries', function (Blueprint $table): void {
            $table->string('letterhead_path')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('subsidiaries', function (Blueprint $table): void {
            $table->dropColumn('letterhead_path');
        });
    }
};
