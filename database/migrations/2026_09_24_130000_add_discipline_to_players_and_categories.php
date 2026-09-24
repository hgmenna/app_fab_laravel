<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->foreignId('discipline_id')
                ->nullable()
                ->after('club_id')
                ->constrained('disciplines')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique('categories_code_unique');
            $table->foreignId('discipline_id')
                ->nullable()
                ->after('id')
                ->constrained('disciplines')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->unique(['discipline_id', 'code']);
        });
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
