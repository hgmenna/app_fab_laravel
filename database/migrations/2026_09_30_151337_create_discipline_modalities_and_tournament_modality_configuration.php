<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('discipline_modalities')) {
            Schema::create('discipline_modalities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('discipline_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('code', 50);
                $table->unsignedSmallInteger('players_per_registration')->default(1);
                $table->unsignedInteger('order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['discipline_id', 'code']);
            });
        }

        if (! Schema::hasTable('tournament_modalities')) {
            Schema::create('tournament_modalities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
                $table->foreignId('discipline_modality_id')->constrained()->cascadeOnDelete();
                $table->json('categories')->nullable();
                $table->timestamps();
                $table->unique(['tournament_id', 'discipline_modality_id']);
            });
        }

        if (! Schema::hasColumn('tournament_category_price', 'tournament_modality_id')) {
            if ($this->indexExists('tournament_category_price', 'tournament_category_price_tournament_id_category_id_unique')) {
                Schema::table('tournament_category_price', fn (Blueprint $table) => $table->dropUnique(['tournament_id', 'category_id']));
            }

            Schema::table('tournament_category_price', function (Blueprint $table) {
                $table->foreignId('tournament_modality_id')->nullable()->after('tournament_id')->constrained()->cascadeOnDelete();
            });
        }

        if (! $this->indexExists('tournament_category_price', 'tcp_modality_category_unique')) {
            Schema::table('tournament_category_price', function (Blueprint $table) {
                $table->unique(['tournament_modality_id', 'category_id'], 'tcp_modality_category_unique');
            });
        }

        if (! Schema::hasColumn('tournament_slots', 'tournament_modality_id')) {
            Schema::table('tournament_slots', function (Blueprint $table) {
                $table->foreignId('tournament_modality_id')->nullable()->after('tournament_id')->constrained()->cascadeOnDelete();
            });
        }

        if (! Schema::hasColumn('tournament_registrations', 'tournament_modality_id')) {
            if ($this->indexExists('tournament_registrations', 'tournament_registrations_tournament_id_player_id_unique')) {
                Schema::table('tournament_registrations', fn (Blueprint $table) => $table->dropUnique(['tournament_id', 'player_id']));
            }

            Schema::table('tournament_registrations', function (Blueprint $table) {
                $table->foreignId('tournament_modality_id')->nullable()->after('tournament_id')->constrained()->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('tournament_registrations', 'player_ids')) {
            Schema::table('tournament_registrations', function (Blueprint $table) {
                $table->json('player_ids')->nullable()->after('partner_player_id');
            });
        }

        if (! Schema::hasTable('tournament_registration_participants')) {
            Schema::create('tournament_registration_participants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tournament_registration_id')->constrained()->cascadeOnDelete();
                $table->foreignId('player_id')->constrained()->cascadeOnDelete();
                $table->unsignedSmallInteger('position')->default(1);
                $table->timestamps();
                $table->unique(['tournament_registration_id', 'player_id'], 'registration_participant_unique');
            });
        }

        $modalities = [];
        DB::table('tournament_types')->select('discipline_id', 'participation_mode')->distinct()->get()->each(function ($type) use (&$modalities): void {
            $mode = $type->participation_mode === 'pairs' ? 'pairs' : 'individual';
            $key = $type->discipline_id.':'.$mode;
            $modalities[$key] ??= DB::table('discipline_modalities')
                ->where('discipline_id', $type->discipline_id)
                ->where('code', $mode)
                ->value('id') ?? DB::table('discipline_modalities')->insertGetId([
                    'discipline_id' => $type->discipline_id,
                    'name' => $mode === 'pairs' ? 'Parejas' : 'Individual',
                    'code' => $mode,
                    'players_per_registration' => $mode === 'pairs' ? 2 : 1,
                    'order' => $mode === 'pairs' ? 2 : 1,
                    'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
                ]);
        });
        DB::table('tournaments')->join('tournament_types', 'tournament_types.id', '=', 'tournaments.tournament_type_id')
            ->select('tournaments.id', 'tournaments.discipline_id', 'tournaments.categories', 'tournament_types.participation_mode')->get()
            ->each(function ($tournament) use (&$modalities): void {
                $mode = $tournament->participation_mode === 'pairs' ? 'pairs' : 'individual';
                $key = $tournament->discipline_id.':'.$mode;
                $modalities[$key] ??= DB::table('discipline_modalities')
                    ->where('discipline_id', $tournament->discipline_id)
                    ->where('code', $mode)
                    ->value('id') ?? DB::table('discipline_modalities')->insertGetId([
                        'discipline_id' => $tournament->discipline_id,
                        'name' => $mode === 'pairs' ? 'Parejas' : 'Individual',
                        'code' => $mode,
                        'players_per_registration' => $mode === 'pairs' ? 2 : 1,
                        'order' => $mode === 'pairs' ? 2 : 1,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                $tm = DB::table('tournament_modalities')
                    ->where('tournament_id', $tournament->id)
                    ->where('discipline_modality_id', $modalities[$key])
                    ->value('id') ?? DB::table('tournament_modalities')->insertGetId([
                        'tournament_id' => $tournament->id,
                        'discipline_modality_id' => $modalities[$key],
                        'categories' => $tournament->categories, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                DB::table('tournament_category_price')->where('tournament_id', $tournament->id)->update(['tournament_modality_id' => $tm]);
                DB::table('tournament_slots')->where('tournament_id', $tournament->id)->update(['tournament_modality_id' => $tm]);
                DB::table('tournament_registrations')->where('tournament_id', $tournament->id)->update(['tournament_modality_id' => $tm]);
            });
        DB::table('tournament_registrations')->select('id', 'player_id', 'partner_player_id')->get()->each(function ($registration): void {
            $playerIds = array_values(array_filter([$registration->player_id, $registration->partner_player_id]));
            DB::table('tournament_registrations')->where('id', $registration->id)->update(['player_ids' => json_encode($playerIds)]);
            foreach ($playerIds as $index => $playerId) {
                DB::table('tournament_registration_participants')->updateOrInsert(
                    ['tournament_registration_id' => $registration->id, 'player_id' => $playerId],
                    ['position' => $index + 1, 'created_at' => now(), 'updated_at' => now()],
                );
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournament_registration_participants');
        Schema::table('tournament_registrations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('tournament_modality_id');
            $table->dropColumn('player_ids');
            $table->unique(['tournament_id', 'player_id']);
        });
        Schema::table('tournament_slots', fn (Blueprint $table) => $table->dropConstrainedForeignId('tournament_modality_id'));
        Schema::table('tournament_category_price', function (Blueprint $table): void {
            $table->dropUnique('tcp_modality_category_unique');
            $table->dropConstrainedForeignId('tournament_modality_id');
            $table->unique(['tournament_id', 'category_id']);
        });
        Schema::dropIfExists('tournament_modalities');
        Schema::dropIfExists('discipline_modalities');
    }

    private function indexExists(string $table, string $index): bool
    {
        return collect(Schema::getIndexes($table))->contains(
            fn (array $definition): bool => ($definition['name'] ?? null) === $index
        );
    }
};
