<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('token', 64)->unique();
            $table->json('items')->default('[]');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'token']);
            $table->index(['store_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
