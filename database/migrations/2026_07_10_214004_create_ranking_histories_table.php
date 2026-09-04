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
        Schema::create('ranking_histories', function (Blueprint $table) {
             $table->id();

            $table->integer('season');
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();

            $table->integer('RG');
            $table->integer('RC');

            $table->string('category')->nullable();

            $table->decimal('total_points',10,2)->default(0);
            $table->integer('total_penalties')->default(0);

            $table->timestamps();

            $table->unique(['season','player_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ranking_histories');
    }
};
