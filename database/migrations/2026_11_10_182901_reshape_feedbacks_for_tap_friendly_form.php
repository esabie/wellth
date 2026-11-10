<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->dropColumn(['improvements', 'shopping_experience']);
        });

        Schema::table('feedbacks', function (Blueprint $table) {
            $table->unsignedTinyInteger('shopping_rating')->after('product_id');
            $table->json('improvement_tags')->after('shopping_rating');
            $table->text('note')->nullable()->after('improvement_tags');
        });
    }

    public function down(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->dropColumn(['shopping_rating', 'improvement_tags', 'note']);
        });

        Schema::table('feedbacks', function (Blueprint $table) {
            $table->text('improvements')->after('product_id');
            $table->text('shopping_experience')->after('improvements');
        });
    }
};
