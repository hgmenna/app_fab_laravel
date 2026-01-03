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
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();

            // Año de la membresía
            $table->year('year');

            // Disciplina a la que pertenece la membresía
            $table->foreignId('discipline_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Monto de la afiliación anual
            $table->decimal('amount', 10, 2);

            // Fecha límite de pago
            $table->date('due_date')->nullable();

            // Si la membresía está activa para ese año
            $table->boolean('active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memberships');
    }
};
