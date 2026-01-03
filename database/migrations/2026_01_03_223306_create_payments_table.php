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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            // Afiliación del jugador a la que corresponde el pago
            $table->foreignId('player_membership_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Quién paga: jugador o club
            $table->enum('payer_type', ['player', 'club']);

            // ID del jugador o club que paga
            $table->unsignedBigInteger('payer_id');

            // Monto del pago
            $table->decimal('amount', 10, 2);

            // Método de pago
            $table->enum('method', ['pluspagos', 'mercadopago', 'manual']);

            // ID externo del proveedor (MP, PlusPagos)
            $table->string('external_reference')->nullable();

            // Estado del pago
            $table->enum('status', ['pending', 'approved', 'failed'])
                ->default('pending');

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
