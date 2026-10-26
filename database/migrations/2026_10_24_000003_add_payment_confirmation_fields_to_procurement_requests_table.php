<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procurement_requests', function (Blueprint $table) {
            $table->timestamp('payment_confirmed_at')->nullable()->index();
            $table->foreignId('payment_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('procurement_requests', function (Blueprint $table) {
            $table->dropForeign(['payment_confirmed_by']);
            $table->dropColumn(['payment_confirmed_at', 'payment_confirmed_by']);
        });
    }
};
