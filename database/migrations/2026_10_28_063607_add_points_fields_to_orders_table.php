<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('points_redeemed')->default(0)->after('promo_code');
            $table->decimal('points_discount_amount', 10, 2)->default(0)->after('points_redeemed');
            $table->unsignedInteger('points_earned')->nullable()->after('points_discount_amount');
            $table->string('points_redeem_status')->default('none')->after('points_earned');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'points_redeemed',
                'points_discount_amount',
                'points_earned',
                'points_redeem_status',
            ]);
        });
    }
};
