<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournament_types', function (Blueprint $table): void {
            $table->string('publication_logo_source', 30)->nullable()->after('is_official');
            $table->foreignId('publication_federation_id')
                ->nullable()
                ->after('publication_logo_source')
                ->constrained('federations')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });

        Schema::table('tournaments', function (Blueprint $table): void {
            $table->string('flyer_path')->nullable()->after('name');
        });

        DB::table('tournament_types')
            ->where('is_official', false)
            ->update(['publication_logo_source' => 'none']);

        DB::table('tournament_types')
            ->where('is_official', true)
            ->update(['publication_logo_source' => 'venue_federation']);

        DB::table('tournament_types')
            ->where('is_official', true)
            ->where(function ($query): void {
                $query->whereRaw('LOWER(name) LIKE ?', ['%campeonato argentino%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%circuito argentino%'])
                    ->orWhereRaw('LOWER(code) LIKE ?', ['%campeonato argentino%'])
                    ->orWhereRaw('LOWER(code) LIKE ?', ['%circuito argentino%']);
            })
            ->update(['publication_logo_source' => 'national_federation']);

        $nationalFederationId = DB::table('federations')
            ->whereRaw('UPPER(short_name) = ?', ['FAB'])
            ->orWhereRaw('LOWER(name) LIKE ?', ['%federacion argentina%'])
            ->orWhereRaw('LOWER(name) LIKE ?', ['%federación argentina%'])
            ->value('id');

        if ($nationalFederationId) {
            DB::table('tournament_types')
                ->where('publication_logo_source', 'national_federation')
                ->update(['publication_federation_id' => $nationalFederationId]);
        }
    }

    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table): void {
            $table->dropColumn('flyer_path');
        });

        Schema::table('tournament_types', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('publication_federation_id');
            $table->dropColumn('publication_logo_source');
        });
    }
};
