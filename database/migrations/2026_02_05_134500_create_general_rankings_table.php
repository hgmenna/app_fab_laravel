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
        Schema::create('general_rankings', function (Blueprint $table) {
            $table->id();
            $table->integer('RG');
            $table->integer('RC');
            $table->string('category')->nullable(); // Para el SelectFilter [5]
            $table->string('last_name')->nullable(); // Para searchable [6]
            $table->string('first_name')->nullable();
            $table->string('club')->nullable(); // Para searchable [7]
            $table->string('fed')->nullable();
            $table->decimal('total_puntos', 10, 2);
            // Campos para las 4 etapas [4]
            for ($i = 1; $i <= 4; $i++) {
                $table->string("pos_$i")->nullable();
                $table->decimal("ptos_$i", 10, 2)->nullable();
            }
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('general_rankings');
    }
};
