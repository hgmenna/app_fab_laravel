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
            $table->unsignedSmallInteger('season')
                ->nullable()
                ->after('player_id');

            $table->string('change_type')
                ->nullable()
                ->after('category_id');

            $table->foreignId('previous_category_id')
                ->nullable()
                ->after('change_type')
                ->constrained('categories')
                ->nullOnDelete();

            $table->date('effective_date')
                ->nullable()
                ->after('ranking_id');

            $table->string('reason')
                ->nullable()
                ->after('effective_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('player_category_histories', function (Blueprint $table) {
            $table->dropForeign(['previous_category_id']);

            $table->dropColumn([
                'season',
                'change_type',
                'previous_category_id',
                'effective_date',
                'reason',
            ]);
        });
    }
};