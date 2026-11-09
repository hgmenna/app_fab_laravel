<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marker_tournament_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tournament_id')->unique()->constrained()->cascadeOnDelete();
            $table->uuid('external_id')->unique();
            $table->string('club_code', 64);
            $table->string('club_name', 160);
            $table->text('token');
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        Schema::create('marker_match_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('marker_tournament_link_id')->constrained()->cascadeOnDelete();
            $table->foreignId('five_quillas_match_id')->unique()->constrained()->cascadeOnDelete();
            $table->uuid('external_id')->unique();
            $table->unsignedSmallInteger('table_number');
            $table->char('revision', 64);
            $table->unsignedInteger('points_per_set');
            $table->unsignedTinyInteger('sets_to_win');
            $table->string('status', 16);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marker_match_assignments');
        Schema::dropIfExists('marker_tournament_links');
    }
};
