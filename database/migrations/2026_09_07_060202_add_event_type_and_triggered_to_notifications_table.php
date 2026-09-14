<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('event_type', 50)->nullable()->after('procurement_request_id');
            $table->foreignId('triggered_by')->nullable()->after('event_type')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign(['triggered_by']);
            $table->dropColumn(['event_type', 'triggered_by']);
        });
    }
};
