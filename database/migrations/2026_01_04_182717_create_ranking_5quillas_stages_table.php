<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ranking_5quillas_stages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ranking_id')
                ->constrained('ranking_5quillas')
                ->cascadeOnDelete();

            $table->integer('stage_number');

            $table->foreignId('tournament_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('points', 10, 2)->default(0);
            $table->integer('position')->nullable();

            $table->timestamp('replaced_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ranking_5quillas_stages');
    }
};
