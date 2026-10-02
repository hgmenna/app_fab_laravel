<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table): void {
            $table->string('venue_type', 20)->default('club')->after('venue_id');
            $table->string('external_venue_name')->nullable()->after('venue_type');
            $table->string('external_venue_address')->nullable()->after('external_venue_name');
            $table->string('external_venue_city')->nullable()->after('external_venue_address');
            $table->foreignId('external_venue_state_id')
                ->nullable()
                ->after('external_venue_city')
                ->constrained('states')
                ->nullOnDelete();
        });

        DB::table('tournaments')
            ->whereNull('venue_id')
            ->update(['venue_type' => 'unassigned']);
    }

    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('external_venue_state_id');
            $table->dropColumn([
                'venue_type',
                'external_venue_name',
                'external_venue_address',
                'external_venue_city',
            ]);
        });
    }
};
