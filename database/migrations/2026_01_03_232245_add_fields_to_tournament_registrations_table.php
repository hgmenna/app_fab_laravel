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
        Schema::table('tournament_registrations', function (Blueprint $table) {

        // Precio final asignado al jugador
        $table->decimal('price', 10, 2)
            ->default(0)
            ->after('status');

        // Estado de pago de la inscripción
        $table->enum('payment_status', ['pending', 'paid'])
            ->default('pending')
            ->after('price');

        // Check-in en sede
        $table->boolean('checked_in')
            ->default(false)
            ->after('payment_status');

        // Fuente de la inscripción
        $table->string('source')
            ->default('manual')
            ->after('checked_in');

        // Notas internas
        $table->text('notes')
            ->nullable()
            ->after('source');
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tournament_registrations', function (Blueprint $table) {
            $table->dropColumn([
                'price',
                'payment_status',
                'checked_in',
                'source',
                'notes',
            ]);
        });

    }
};
