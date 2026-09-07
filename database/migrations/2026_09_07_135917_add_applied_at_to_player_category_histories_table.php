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
            $table->timestamp('applied_at')
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
            $table->dropColumn('applied_at');
        });
    }
};
