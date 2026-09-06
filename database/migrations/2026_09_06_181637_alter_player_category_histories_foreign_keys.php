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
        Schema::table('player_category_histories', function (Blueprint $table) {
            $table->dropForeign(['player_id']);
            $table->dropForeign(['category_id']);
            $table->dropForeign(['previous_category_id']);
        });

        Schema::table('player_category_histories', function (Blueprint $table) {
            $table->foreign('player_id')
                ->references('id')
                ->on('players')
                ->restrictOnDelete();

            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->restrictOnDelete();

            $table->foreign('previous_category_id')
                ->references('id')
                ->on('categories')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('player_category_histories', function (Blueprint $table) {
            $table->dropForeign(['player_id']);
            $table->dropForeign(['category_id']);
            $table->dropForeign(['previous_category_id']);
        });

        Schema::table('player_category_histories', function (Blueprint $table) {
            $table->foreign('player_id')
                ->references('id')
                ->on('players')
                ->cascadeOnDelete();

            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->cascadeOnDelete();

            $table->foreign('previous_category_id')
                ->references('id')
                ->on('categories')
                ->nullOnDelete();
        });
    }
};