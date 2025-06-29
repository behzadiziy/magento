<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateStoresTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            // Drop old 'status' column
            $table->dropColumn('status');

            // Add new 'is_active' column
            $table->boolean('is_active')->default(1);

            // Add new columns
            $table->unsignedInteger('group_id');
            $table->unsignedInteger('website_id');
            $table->smallInteger('sort_order')->default(0);
            $table->string('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            // Revert changes
            $table->dropColumn(['is_active', 'group_id', 'website_id', 'sort_order', 'code']);
            $table->enum('status', ['active', 'inactive', 'pending'])->default('pending');
        });
    }
}
