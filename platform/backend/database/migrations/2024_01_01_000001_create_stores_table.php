<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('domain')->nullable()->unique();
            $table->string('custom_domain')->nullable()->unique();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            
            // Contact information
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->json('address')->nullable();
            
            // Localization
            $table->string('currency', 3)->default('USD');
            $table->string('timezone')->default('UTC');
            $table->string('language', 2)->default('en');
            
            // Branding
            $table->string('logo_url')->nullable();
            $table->string('favicon_url')->nullable();
            
            // Configuration
            $table->json('settings')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
