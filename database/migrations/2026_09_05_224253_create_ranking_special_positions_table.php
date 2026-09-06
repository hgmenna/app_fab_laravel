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
        Schema::create('ranking_special_positions', function (Blueprint $table) {
            $table->id();

            $table->unsignedSmallInteger('season')->unique();

            $table->foreignId('champion_player_id')
                ->constrained('players')
                ->restrictOnDelete();

            $table->foreignId('runner_up_player_id')
                ->constrained('players')
                ->restrictOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ranking_special_positions');
    }
};
