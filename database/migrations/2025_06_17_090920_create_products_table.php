<?php

use App\Enums\ProductStatus;
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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('name');
            $table->text('description')->nullable();

            $table->decimal('price', 10, 2);
            $table->string('status')->default('pending_review')->index(); // pending_review, approved, rejected, synced, sync_failed
            $table->json('crawler_payload')->nullable();
            $table->unsignedBigInteger('magento_product_id')->nullable();
            $table->unsignedInteger('price')->default(0);
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->string('status')->default(ProductStatus::PendingReview->value)->index();
            $table->json('images')->nullable();
            $table->json('attributes')->nullable();
            $table->string('category')->nullable();
            $table->string('source_url', 500)->nullable();
            $table->string('brand')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
