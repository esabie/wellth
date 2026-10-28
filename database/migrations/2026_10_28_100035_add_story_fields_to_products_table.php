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
        Schema::table('products', function (Blueprint $table) {
            $table->string('bottle_size')->nullable()->after('short_description');
            $table->string('taste')->nullable()->after('description');
            $table->string('ritual')->nullable()->after('taste');
            $table->json('highlights')->nullable()->after('ritual');
            $table->json('ingredients')->nullable()->after('highlights');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'bottle_size',
                'taste',
                'ritual',
                'highlights',
                'ingredients',
            ]);
        });
    }
};
