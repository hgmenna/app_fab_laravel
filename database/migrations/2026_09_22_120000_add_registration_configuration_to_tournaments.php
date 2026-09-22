<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournament_types', function (Blueprint $table) {
            $table->string('participation_mode', 20)
                ->default('individual')
                ->after('code');
            $table->boolean('has_handicap')
                ->default(false)
                ->after('participation_mode');
        });

        Schema::table('tournaments', function (Blueprint $table) {
            $table->boolean('registration_enabled')
                ->default(false)
                ->after('scoring_rules');
        });

        DB::table('tournaments')
            ->whereNotNull('registration_open_at')
            ->whereNotNull('registration_close_at')
            ->update(['registration_enabled' => true]);

        Schema::table('tournament_category_price', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('tournament_category_price')
            ->whereNull('price')
            ->update(['price' => 0]);

        Schema::table('tournament_category_price', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->nullable(false)->change();
        });

        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn('registration_enabled');
        });

        Schema::table('tournament_types', function (Blueprint $table) {
            $table->dropColumn(['participation_mode', 'has_handicap']);
        });
    }
};
