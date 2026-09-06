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
        Schema::create('player_category_promotions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('player_id')
                ->constrained()
                ->restrictOnDelete();

            $table->unsignedSmallInteger('season');

            $table->foreignId('previous_category_id')
                ->constrained('categories')
                ->restrictOnDelete();

            $table->foreignId('new_category_id')
                ->constrained('categories')
                ->restrictOnDelete();

            /*
             * Motivo del ascenso:
             *
             * ranking_zone:
             * El jugador terminó dentro de la zona temporal
             * Master/Nacional y asciende directamente a Primera.
             *
             * category_rc1:
             * El jugador terminó RC1 de su categoría permanente.
             */
            $table->string('promotion_type');

            /*
             * Datos del ranking que originaron la promoción.
             * Se guardan como evidencia del cierre de temporada.
             */
            $table->unsignedInteger('final_rg')->nullable();
            $table->unsignedInteger('final_rc')->nullable();

            $table->date('effective_date');

            /*
             * Fecha y hora en que realmente se modificó players.category_id.
             * NULL significa que el ascenso todavía está pendiente.
             */
            $table->timestamp('applied_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            /*
             * Un jugador solamente puede tener una promoción
             * determinada por cada temporada.
             */
            $table->unique(
                ['player_id', 'season'],
                'player_category_promotions_player_season_unique'
            );

            $table->index(
                ['season', 'effective_date', 'applied_at'],
                'pcp_season_effective_applied_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_category_promotions');
    }
};