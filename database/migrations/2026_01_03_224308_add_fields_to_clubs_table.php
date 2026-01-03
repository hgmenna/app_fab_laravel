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
        Schema::table('clubs', function (Blueprint $table) {
            // Código institucional único
            $table->string('federation_code')
                ->nullable()
                ->after('short_name');

            // Estado del club
            $table->boolean('is_active')
                ->default(true)
                ->after('address');

            // Datos administrativos opcionales
            $table->string('tax_id')
                ->nullable()
                ->after('is_active');

            // Persona de contacto
            $table->string('contact_person')
                ->nullable()
                ->after('mail_contact');

            // Notas internas
            $table->text('notes')
                ->nullable()
                ->after('contact_person');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clubs', function (Blueprint $table) {
                $table->dropColumn([
                'federation_code',
                'is_active',
                'tax_id',
                'contact_person',
                'notes',
            ]);

        });
    }
};
