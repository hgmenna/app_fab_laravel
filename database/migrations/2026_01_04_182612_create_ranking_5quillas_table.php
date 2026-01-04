<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ranking_5quillas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('club_id')->nullable()->constrained()->nullOnDelete();

            $table->decimal('total_points', 10, 2)->default(0);

            $table->integer('rg_position')->nullable();
            $table->integer('rc_position')->nullable();

            $table->decimal('stage_1_points', 10, 2)->nullable();
            $table->integer('stage_1_position')->nullable();

            $table->decimal('stage_2_points', 10, 2)->nullable();
            $table->integer('stage_2_position')->nullable();

            $table->decimal('stage_3_points', 10, 2)->nullable();
            $table->integer('stage_3_position')->nullable();

            $table->decimal('stage_4_points', 10, 2)->nullable();
            $table->integer('stage_4_position')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ranking_5quillas');
    }
};
