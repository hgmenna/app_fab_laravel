<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournament_regulation_settings', function (Blueprint $table): void {
            $table->boolean('check_club_category_quota')->default(false)
                ->after('block_non_official_against_official_same_state');
            $table->unsignedSmallInteger('max_non_official_tournaments_per_category')->nullable()
                ->after('check_club_category_quota');
            $table->unsignedSmallInteger('club_category_period_months')->nullable()
                ->after('max_non_official_tournaments_per_category');
        });
    }

    public function down(): void
    {
        Schema::table('tournament_regulation_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'check_club_category_quota',
                'max_non_official_tournaments_per_category',
                'club_category_period_months',
            ]);
        });
    }
};
