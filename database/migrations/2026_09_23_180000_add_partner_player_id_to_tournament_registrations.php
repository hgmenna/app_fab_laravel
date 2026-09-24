<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournament_registrations', function (Blueprint $table) {
            $table->foreignId('partner_player_id')
                ->nullable()
                ->after('player_id')
                ->constrained('players')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->index(['tournament_id', 'partner_player_id']);
        });
    }

    public function down(): void
    {
        Schema::table('tournament_registrations', function (Blueprint $table) {
            $table->dropIndex(['tournament_id', 'partner_player_id']);
            $table->dropConstrainedForeignId('partner_player_id');
        });
    }
};
