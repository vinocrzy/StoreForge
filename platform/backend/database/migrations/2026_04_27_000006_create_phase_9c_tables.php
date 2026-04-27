<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Loyalty points ledger
        Schema::create('loyalty_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->integer('points'); // positive = earned, negative = redeemed
            $table->string('type'); // 'earned_order', 'redeemed', 'adjusted', 'expired'
            $table->string('description')->nullable();
            $table->nullableMorphs('source'); // e.g. Order, Coupon
            $table->integer('balance_after'); // running balance after this transaction
            $table->timestamps();

            $table->index(['store_id', 'customer_id']);
            $table->index(['store_id', 'created_at']);
        });

        // Add points_balance to customers table
        Schema::table('customers', function (Blueprint $table) {
            $table->integer('loyalty_points_balance')->default(0)->after('accepts_marketing');
        });

        // Newsletter subscribers
        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('first_name')->nullable();
            $table->string('status')->default('subscribed'); // subscribed, unsubscribed
            $table->string('source')->nullable(); // 'footer_form', 'checkout', 'account'
            $table->timestamp('subscribed_at')->useCurrent();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'email']);
            $table->index('store_id');
        });

        // Product co-purchase recommendation cache
        Schema::create('product_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recommended_product_id')->constrained('products')->cascadeOnDelete();
            $table->string('type'); // 'bought_together', 'similar'
            $table->integer('score')->default(0); // co-purchase frequency or relevance
            $table->timestamp('computed_at')->useCurrent();

            $table->unique(['store_id', 'product_id', 'recommended_product_id', 'type'], 'uk_product_recommendation');
            $table->index(['store_id', 'product_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_recommendations');
        Schema::dropIfExists('newsletter_subscribers');
        Schema::table('customers', fn (Blueprint $t) => $t->dropColumn('loyalty_points_balance'));
        Schema::dropIfExists('loyalty_points');
    }
};
