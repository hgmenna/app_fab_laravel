<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tournaments', 'venue_type')) {
            Schema::table('tournaments', function (Blueprint $table): void {
                $table->string('venue_type', 20)->default('club')->after('venue_id');
            });
        }

        if (! Schema::hasColumn('tournaments', 'external_venue_name')) {
            Schema::table('tournaments', function (Blueprint $table): void {
                $table->string('external_venue_name')->nullable()->after('venue_type');
            });
        }

        if (! Schema::hasColumn('tournaments', 'external_venue_address')) {
            Schema::table('tournaments', function (Blueprint $table): void {
                $table->string('external_venue_address')->nullable()->after('external_venue_name');
            });
        }

        if (! Schema::hasColumn('tournaments', 'external_venue_city')) {
            Schema::table('tournaments', function (Blueprint $table): void {
                $table->string('external_venue_city')->nullable()->after('external_venue_address');
            });
        }

        if (! Schema::hasColumn('tournaments', 'external_venue_state_id')) {
            Schema::table('tournaments', function (Blueprint $table): void {
                $table->unsignedBigInteger('external_venue_state_id')
                    ->nullable()
                    ->after('external_venue_city');
            });
        }

        DB::table('tournaments')
            ->whereNull('venue_id')
            ->where(function ($query): void {
                $query->whereNull('venue_type')
                    ->orWhere('venue_type', 'club');
            })
            ->update(['venue_type' => 'unassigned']);
    }

    public function down(): void
    {
        $columns = collect([
            'venue_type',
            'external_venue_name',
            'external_venue_address',
            'external_venue_city',
            'external_venue_state_id',
        ])->filter(fn (string $column): bool => Schema::hasColumn('tournaments', $column))->all();

        if ($columns !== []) {
            Schema::table('tournaments', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }
};
