<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promo_codes', function (Blueprint $table) {
            $table->string('referrer_phone')->nullable()->after('is_active');
            $table->string('referrer_phone_normalized')->nullable()->after('referrer_phone');
            $table->index('referrer_phone_normalized');
        });
    }

    public function down(): void
    {
        Schema::table('promo_codes', function (Blueprint $table) {
            $table->dropIndex(['referrer_phone_normalized']);
            $table->dropColumn(['referrer_phone', 'referrer_phone_normalized']);
        });
    }
};
