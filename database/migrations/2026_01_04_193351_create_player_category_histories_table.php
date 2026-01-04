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
        Schema::create('player_category_histories', function (Blueprint $table) {
             $table->id();

            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();

            $table->string('source'); // tournament, ranking, manual, affiliation

            $table->foreignId('tournament_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('ranking_id')
                ->nullable()
                ->constrained('ranking_5quillas')
                ->nullOnDelete();

            $table->text('notes')->nullable();

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_category_histories');
    }
};
