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
        Schema::table('general_rankings', function (Blueprint $table) {

            $table->foreignId('player_id')
                ->after('id')
                ->nullable()
                ->constrained();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('general_rankings', function (Blueprint $table) {
            //
        });
    }
};
