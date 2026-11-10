<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->date('feedback_auto_invite_on')->nullable()->after('feedback_invited_at');
            $table->index('feedback_auto_invite_on');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['feedback_auto_invite_on']);
            $table->dropColumn('feedback_auto_invite_on');
        });
    }
};
