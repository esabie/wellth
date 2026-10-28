<?php

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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('customer_phone_normalized')->nullable()->after('customer_phone');
            $table->decimal('loyalty_discount_amount', 10, 2)->default(0)->after('discount_amount');
            $table->index('customer_phone_normalized');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['customer_phone_normalized']);
            $table->dropColumn(['customer_phone_normalized', 'loyalty_discount_amount']);
        });
    }
};
