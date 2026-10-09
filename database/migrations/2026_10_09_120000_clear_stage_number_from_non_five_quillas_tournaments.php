<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $fiveQuillasIds = DB::table('disciplines')
            ->where('code', 'five_quillas')
            ->pluck('id');

        if ($fiveQuillasIds->isEmpty()) {
            return;
        }

        DB::table('tournaments')
            ->whereNotNull('stage_number')
            ->where(function ($query) use ($fiveQuillasIds): void {
                $query->whereNull('discipline_id')
                    ->orWhereNotIn('discipline_id', $fiveQuillasIds);
            })
            ->update([
                'stage_number' => null,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Los números de etapa ajenos a 5 Quillas eran datos inválidos y no se restauran.
    }
};
