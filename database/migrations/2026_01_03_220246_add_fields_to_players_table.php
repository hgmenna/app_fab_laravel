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
        Schema::table('players', function (Blueprint $table) {
            // Datos adicionales del jugador
            $table->string('document_type')->nullable()->after('document_number');
            $table->string('nationality')->nullable()->after('document_type');
            $table->string('gender')->nullable()->after('birth_date');

            // Foto del jugador
            $table->string('photo_path')->nullable()->after('phone');

            // Habilitación deportiva general
            $table->boolean('is_enabled_to_compete')->default(false)->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn([
                'document_type',
                'nationality',
                'gender',
                'photo_path',
                'is_enabled_to_compete',
            ]);
        });
    }
};
