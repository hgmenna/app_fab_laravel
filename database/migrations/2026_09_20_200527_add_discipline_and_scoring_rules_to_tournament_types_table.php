<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournament_types', function (Blueprint $table) {
            /*
             * Cada tipo de torneo pertenece a una disciplina.
             *
             * Se mantiene nullable durante esta primera implementación
             * para permitir la migración de los tipos existentes que
             * todavía no tengan una disciplina configurada.
             */
            $table->foreignId('discipline_id')
                ->nullable()
                ->after('id')
                ->constrained('disciplines')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
             * Identifica el método utilizado para calcular los puntos.
             *
             * Para 5 Quillas se utilizará:
             * position
             */
            $table->string('scoring_method', 50)
                ->nullable()
                ->after('score_percentage');

            /*
             * Tabla de puntuación propia del tipo de torneo.
             *
             * Ejemplo:
             *
             * [
             *     {
             *         "instance_code": "92",
             *         "description": "1°",
             *         "instance_value": 92,
             *         "points": 75
             *     }
             * ]
             */
            $table->json('scoring_rules')
                ->nullable()
                ->after('scoring_method');
        });

        /*
         * Vincular automáticamente los tipos existentes cuando todos
         * los torneos de ese tipo pertenecen a una única disciplina.
         *
         * No se utilizan nombres, códigos ni IDs fijos de tipos.
         */
        $existingAssignments = DB::table('tournaments')
            ->select('tournament_type_id')
            ->selectRaw('MIN(discipline_id) as discipline_id')
            ->whereNotNull('tournament_type_id')
            ->whereNotNull('discipline_id')
            ->groupBy('tournament_type_id')
            ->havingRaw('COUNT(DISTINCT discipline_id) = 1')
            ->get();

        foreach ($existingAssignments as $assignment) {
            DB::table('tournament_types')
                ->where('id', $assignment->tournament_type_id)
                ->update([
                    'discipline_id' => $assignment->discipline_id,
                ]);
        }

        /*
         * Por el momento solamente 5 Quillas implementa el cálculo
         * de puntos basado en posiciones.
         *
         * Se identifica la disciplina mediante su código estable,
         * no mediante un ID fijo.
         */
        $fiveQuillasId = DB::table('disciplines')
            ->where('code', 'five_quillas')
            ->value('id');

        if ($fiveQuillasId) {
            DB::table('tournament_types')
                ->where('discipline_id', $fiveQuillasId)
                ->update([
                    'scoring_method' => 'position',
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('tournament_types', function (Blueprint $table) {
            $table->dropConstrainedForeignId('discipline_id');

            $table->dropColumn([
                'scoring_method',
                'scoring_rules',
            ]);
        });
    }
};