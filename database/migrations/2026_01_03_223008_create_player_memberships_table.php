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
        Schema::create('player_memberships', function (Blueprint $table) {
             $table->id();

            // Jugador afiliado
            $table->foreignId('player_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Membresía (año + disciplina)
            $table->foreignId('membership_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Club que paga (opcional)
            $table->foreignId('club_id')
                ->nullable()
                ->constrained()
                ->cascadeOnUpdate()
                ->nullOnDelete();

            // Monto total que debe pagar el jugador
            $table->decimal('amount_due', 10, 2);

            // Monto pagado hasta el momento
            $table->decimal('amount_paid', 10, 2)->default(0);

            // Estado de la afiliación
            $table->enum('status', ['pending', 'partial', 'paid', 'expired'])
                ->default('pending');

            // Habilitación deportiva por disciplina
            $table->boolean('enabled_to_compete')->default(false);

            // Fecha en que quedó completamente pago
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_memberships');
    }
};
