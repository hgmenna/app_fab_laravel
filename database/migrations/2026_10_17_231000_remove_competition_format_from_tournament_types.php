<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('tournament_types', 'competition_format')) {
            Schema::table('tournament_types', function (Blueprint $table): void {
                $table->dropColumn('competition_format');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('tournament_types', 'competition_format')) {
            Schema::table('tournament_types', function (Blueprint $table): void {
                $table->string('competition_format', 40)->default('zones_final');
            });
        }
    }
};
