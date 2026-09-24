<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('players', 'discipline_id')) {
            Schema::table('players', function (Blueprint $table) {
                $table->foreignId('discipline_id')
                    ->nullable()
                    ->after('club_id')
                    ->constrained('disciplines')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
            });
        }

        $codeUniqueIndex = collect(Schema::getIndexes('categories'))
            ->first(fn (array $index): bool => ($index['unique'] ?? false)
                && ($index['columns'] ?? []) === ['code']);

        if ($codeUniqueIndex) {
            Schema::table('categories', function (Blueprint $table) use ($codeUniqueIndex) {
                $table->dropUnique($codeUniqueIndex['name']);
            });
        }

        if (! Schema::hasColumn('categories', 'discipline_id')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->foreignId('discipline_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('disciplines')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();
            });
        }

        $hasDisciplineCodeUnique = collect(Schema::getIndexes('categories'))
            ->contains(fn (array $index): bool => ($index['unique'] ?? false)
                && ($index['columns'] ?? []) === ['discipline_id', 'code']);

        if (! $hasDisciplineCodeUnique) {
            Schema::table('categories', function (Blueprint $table) {
                $table->unique(['discipline_id', 'code']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['discipline_id', 'code']);
            $table->dropConstrainedForeignId('discipline_id');
            $table->unique('code');
        });

        Schema::table('players', function (Blueprint $table) {
            $table->dropConstrainedForeignId('discipline_id');
        });
    }
};
