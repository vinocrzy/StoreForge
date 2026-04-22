<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['store_id', 'customer_id', 'product_id'], 'uk_customer_product');
            $table->index('store_id', 'idx_store_id');
            $table->index('customer_id', 'idx_customer_id');
            $table->index('product_id', 'idx_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlists');
    }
};
