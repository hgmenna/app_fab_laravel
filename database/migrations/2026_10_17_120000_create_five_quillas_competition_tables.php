<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('five_quillas_competitions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tournament_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('public_slug')->unique();
            $table->enum('status', ['draft', 'published', 'in_progress', 'completed'])->default('draft');
            $table->boolean('is_public')->default(false);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('five_quillas_phases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('competition_id')->constrained('five_quillas_competitions')->cascadeOnDelete();
            $table->string('code', 60);
            $table->string('name');
            $table->enum('phase_type', ['zones', 'slot_elimination', 'play_in', 'bracket'])->default('zones');
            $table->unsignedSmallInteger('sort_order')->default(1);
            $table->unsignedTinyInteger('sets_to_play')->default(3);
            $table->unsignedSmallInteger('points_per_set')->default(60);
            $table->enum('seeding_method', ['previous_results', 'national_ranking', 'automatic_draw', 'number_draw', 'manual'])->default('manual');
            $table->boolean('split_by_slot')->default(false);
            $table->enum('status', ['pending', 'ready', 'in_progress', 'completed'])->default('pending');
            $table->boolean('is_public')->default(false);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->unique(['competition_id', 'code']);
        });

        Schema::create('five_quillas_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('competition_id')->constrained('five_quillas_competitions')->cascadeOnDelete();
            $table->foreignId('tournament_registration_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('registration_participant_id')->nullable()->constrained('tournament_registration_participants')->nullOnDelete();
            $table->foreignId('player_id')->constrained()->restrictOnDelete();
            $table->foreignId('source_slot_id')->nullable()->constrained('tournament_slots')->nullOnDelete();
            $table->unsignedInteger('national_ranking_snapshot')->nullable();
            $table->unsignedSmallInteger('seed')->nullable();
            $table->enum('status', ['eligible', 'assigned', 'eliminated', 'qualified', 'withdrawn'])->default('eligible');
            $table->timestamps();
            $table->unique(['competition_id', 'player_id']);
        });

        Schema::create('five_quillas_groups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('phase_id')->constrained('five_quillas_phases')->cascadeOnDelete();
            $table->foreignId('tournament_slot_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('sort_order')->default(1);
            $table->enum('status', ['draft', 'confirmed', 'in_progress', 'completed'])->default('draft');
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });

        Schema::create('five_quillas_group_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('group_id')->constrained('five_quillas_groups')->cascadeOnDelete();
            $table->foreignId('entry_id')->constrained('five_quillas_entries')->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->unsignedSmallInteger('match_wins')->default(0);
            $table->smallInteger('sets_difference')->default(0);
            $table->unsignedInteger('points_for')->default(0);
            $table->unsignedInteger('points_against')->default(0);
            $table->decimal('points_average', 8, 3)->default(0);
            $table->unsignedTinyInteger('final_place')->nullable();
            $table->timestamps();
            $table->unique(['group_id', 'entry_id']);
            $table->unique(['group_id', 'position']);
        });

        Schema::create('five_quillas_matches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('phase_id')->constrained('five_quillas_phases')->cascadeOnDelete();
            $table->foreignId('group_id')->nullable()->constrained('five_quillas_groups')->cascadeOnDelete();
            $table->unsignedSmallInteger('round_number')->default(1);
            $table->unsignedSmallInteger('bracket_order')->default(1);
            $table->string('label')->nullable();
            $table->foreignId('player_one_entry_id')->nullable()->constrained('five_quillas_entries')->nullOnDelete();
            $table->foreignId('player_two_entry_id')->nullable()->constrained('five_quillas_entries')->nullOnDelete();
            $table->json('set_scores')->nullable();
            $table->foreignId('winner_entry_id')->nullable()->constrained('five_quillas_entries')->nullOnDelete();
            $table->foreignId('next_match_id')->nullable()->constrained('five_quillas_matches')->nullOnDelete();
            $table->unsignedTinyInteger('next_match_position')->nullable();
            $table->foreignId('loser_next_match_id')->nullable()->constrained('five_quillas_matches')->nullOnDelete();
            $table->unsignedTinyInteger('loser_next_match_position')->nullable();
            $table->unsignedSmallInteger('table_number')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->unsignedSmallInteger('estimated_minutes')->nullable();
            $table->boolean('is_bye')->default(false);
            $table->enum('status', ['pending', 'scheduled', 'called', 'in_progress', 'completed', 'suspended', 'rescheduled'])->default('pending');
            $table->boolean('is_public')->default(false);
            $table->timestamps();
            $table->unique(['phase_id', 'group_id', 'round_number', 'bracket_order'], 'five_quillas_match_position_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('five_quillas_matches');
        Schema::dropIfExists('five_quillas_group_entries');
        Schema::dropIfExists('five_quillas_groups');
        Schema::dropIfExists('five_quillas_entries');
        Schema::dropIfExists('five_quillas_phases');
        Schema::dropIfExists('five_quillas_competitions');
    }
};
