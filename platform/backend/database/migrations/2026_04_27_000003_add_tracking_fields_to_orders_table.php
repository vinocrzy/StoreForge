<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('shipping_method_id')->nullable()->after('shipping_amount')
                ->constrained('shipping_methods')->nullOnDelete();
            $table->string('tracking_number', 255)->nullable()->after('shipping_method_id');
            $table->string('tracking_carrier', 100)->nullable()->after('tracking_number');
            $table->string('tracking_url', 500)->nullable()->after('tracking_carrier');
            $table->timestamp('estimated_delivery_at')->nullable()->after('tracking_url');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['shipping_method_id']);
            $table->dropColumn([
                'shipping_method_id',
                'tracking_number',
                'tracking_carrier',
                'tracking_url',
                'estimated_delivery_at',
            ]);
        });
    }
};
