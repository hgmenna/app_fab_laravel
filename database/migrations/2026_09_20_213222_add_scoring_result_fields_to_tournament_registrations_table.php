<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournament_registrations', function (Blueprint $table) {
            /*
             * Código del resultado definido dentro del array
             * de puntuación del tipo de torneo.
             *
             * Ejemplos:
             * 92, CHAMPION, SEMIFINAL, QUARTER_FINAL.
             */
            $table->string('result_code', 50)
                ->nullable()
                ->after('tournament_instance_id');

            /*
             * Copia histórica de la descripción que tenía la regla
             * cuando se asignó el resultado.
             */
            $table->string('result_description')
                ->nullable()
                ->after('result_code');

            /*
             * Valor utilizado para ordenar resultados deportivos.
             * Un valor mayor representa un mejor resultado.
             */
            $table->unsignedInteger('result_instance_value')
                ->nullable()
                ->after('result_description');

            /*
             * Facilita consultas estadísticas por torneo y resultado.
             */
            $table->index(
                ['tournament_id', 'result_code'],
                'tr_tournament_result_code_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('tournament_registrations', function (Blueprint $table) {
            $table->dropIndex('tr_tournament_result_code_idx');

            $table->dropColumn([
                'result_code',
                'result_description',
                'result_instance_value',
            ]);
        });
    }
};
