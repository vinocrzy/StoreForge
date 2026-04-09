<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('group', 50); // general, branding, policies, checkout, payments, shipping, taxes, seo, notifications, security
            $table->string('key', 100);
            $table->longText('value')->nullable();
            $table->string('type', 20)->default('string'); // string, integer, boolean, json
            $table->string('description', 255)->nullable();
            $table->boolean('is_public')->default(false); // exposed to storefront API
            $table->timestamps();

            $table->unique(['store_id', 'group', 'key']);
            $table->index(['store_id', 'group']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};
