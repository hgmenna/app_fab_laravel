<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('tournaments')
            ->where('status', 'draft')
            ->update([
                'status' => 'published',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // No se revierten estados para no convertir en borrador torneos
        // que hayan sido publicados posteriormente de forma intencional.
    }
};
