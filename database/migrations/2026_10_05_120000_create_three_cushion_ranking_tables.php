<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('three_cushion_stage_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tournament_registration_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('caroms');
            $table->unsignedInteger('innings');
            $table->decimal('general_average', 14, 6)->default(0);
            $table->decimal('best_match_average', 14, 6)->default(0);
            $table->unsignedInteger('high_run')->default(0);
            $table->date('high_run_achieved_at')->nullable();
            $table->timestamps();

            $table->index(['category_id', 'player_id']);
        });

        Schema::create('three_cushion_rankings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('season');
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->unsignedInteger('total_caroms')->default(0);
            $table->unsignedInteger('total_innings')->default(0);
            $table->unsignedInteger('high_run')->default(0);
            $table->date('high_run_achieved_at')->nullable();
            $table->decimal('general_average', 14, 6)->default(0);
            $table->decimal('best_match_average', 14, 6)->default(0);
            $table->decimal('ranking_points', 12, 2)->default(0);
            $table->unsignedInteger('stages_played')->default(0);
            $table->unsignedInteger('total_stages')->default(0);
            $table->timestamps();

            $table->unique(['season', 'category_id', 'player_id'], 'three_cushion_ranking_unique');
            $table->index(['season', 'category_id', 'position'], 'three_cushion_ranking_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('three_cushion_rankings');
        Schema::dropIfExists('three_cushion_stage_results');
    }
};
