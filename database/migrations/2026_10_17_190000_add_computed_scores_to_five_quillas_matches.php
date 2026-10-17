<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('five_quillas_matches', function (Blueprint $table): void {
            $table->unsignedTinyInteger('player_one_actual_sets')->default(0)->after('set_scores');
            $table->unsignedTinyInteger('player_two_actual_sets')->default(0)->after('player_one_actual_sets');
            $table->unsignedTinyInteger('player_one_computed_sets')->default(0)->after('player_two_actual_sets');
            $table->unsignedTinyInteger('player_two_computed_sets')->default(0)->after('player_one_computed_sets');
            $table->unsignedInteger('player_one_points')->default(0)->after('player_two_computed_sets');
            $table->unsignedInteger('player_two_points')->default(0)->after('player_one_points');
        });
    }

    public function down(): void
    {
        Schema::table('five_quillas_matches', function (Blueprint $table): void {
            $table->dropColumn([
                'player_one_actual_sets', 'player_two_actual_sets',
                'player_one_computed_sets', 'player_two_computed_sets',
                'player_one_points', 'player_two_points',
            ]);
        });
    }
};
