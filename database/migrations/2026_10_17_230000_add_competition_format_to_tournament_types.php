<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tournament_types', function (Blueprint $table): void {
            $table->string('competition_format', 40)->default('zones_final')->after('scoring_rules');
        });

        DB::table('tournament_types')
            ->where(function ($query): void {
                $query->whereRaw('LOWER(COALESCE(code, \'\')) LIKE ?', ['%circuit%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%circuit%']);
            })
            ->update(['competition_format' => 'circuit_argentino']);
    }

    public function down(): void
    {
        Schema::table('tournament_types', function (Blueprint $table): void {
            $table->dropColumn('competition_format');
        });
    }
};
