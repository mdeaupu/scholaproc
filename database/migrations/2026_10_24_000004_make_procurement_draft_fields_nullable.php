<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procurement_requests', function (Blueprint $table) {
            $table->foreignId('package_category_id')->nullable()->change();
            $table->foreignId('budget_year_id')->nullable()->change();
            $table->foreignId('funding_source_id')->nullable()->change();
        });

        Schema::table('procurement_request_items', function (Blueprint $table) {
            $table->string('item_name')->nullable()->change();
            $table->text('specification')->nullable()->change();
            $table->foreignId('unit_id')->nullable()->change();
            $table->unsignedInteger('quantity')->nullable()->change();
            $table->decimal('estimated_price', 15, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('procurement_request_items', function (Blueprint $table) {
            $table->string('item_name')->nullable(false)->change();
            $table->text('specification')->nullable(false)->change();
            $table->foreignId('unit_id')->nullable(false)->change();
            $table->unsignedInteger('quantity')->nullable(false)->change();
            $table->decimal('estimated_price', 15, 2)->nullable(false)->change();
        });

        Schema::table('procurement_requests', function (Blueprint $table) {
            $table->foreignId('package_category_id')->nullable(false)->change();
            $table->foreignId('budget_year_id')->nullable(false)->change();
            $table->foreignId('funding_source_id')->nullable(false)->change();
        });
    }
};
