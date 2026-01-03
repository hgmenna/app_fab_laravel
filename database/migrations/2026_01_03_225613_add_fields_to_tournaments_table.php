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
        Schema::table('tournaments', function (Blueprint $table) {

        // Disciplina del torneo
        $table->foreignId('discipline_id')
            ->after('name')
            ->constrained()
            ->cascadeOnUpdate()
            ->restrictOnDelete();

        // Estado del torneo
        $table->enum('status', [
            'draft',
            'published',
            'in_progress',
            'finished',
            'cancelled'
        ])->default('draft')->after('end_date');

        // Reglas específicas del torneo
        $table->json('scoring_rules')->nullable()->after('status');

        // Inscripciones
        $table->timestamp('registration_open_at')->nullable()->after('scoring_rules');
        $table->timestamp('registration_close_at')->nullable()->after('registration_open_at');

        // Costo de inscripción (opcional)
        $table->decimal('entry_fee', 10, 2)->nullable()->after('registration_close_at');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn([
                'discipline_id',
                'status',
                'scoring_rules',
                'registration_open_at',
                'registration_close_at',
                'entry_fee',
            ]);
        });
    }


};
