<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('owner_id');
            $table->string('domain_name');
            $table->string('store_category');
            $table->date('registration_date');
            $table->date('expiration_date');
            $table->boolean('is_active')->default(1);
            $table->unsignedInteger('group_id');
            $table->unsignedInteger('website_id');
            $table->smallInteger('sort_order')->default(0);
            $table->string('code');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
