<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marker_tournament_links', function (Blueprint $table): void {
            $table->string('marker_username', 80)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('marker_tournament_links', function (Blueprint $table): void {
            $table->dropColumn('marker_username');
        });
    }
};
