<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->timestamp('last_activity_at')->nullable()->after('expires_at');
            $table->timestamp('abandoned_at')->nullable()->after('last_activity_at');
            $table->timestamp('recovery_email_sent_at')->nullable()->after('abandoned_at');
            $table->tinyInteger('recovery_email_count')->default(0)->after('recovery_email_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropColumn([
                'last_activity_at',
                'abandoned_at',
                'recovery_email_sent_at',
                'recovery_email_count',
            ]);
        });
    }
};
