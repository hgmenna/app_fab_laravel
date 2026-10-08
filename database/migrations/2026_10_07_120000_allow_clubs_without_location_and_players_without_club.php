<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clubs', function (Blueprint $table): void {
            $table->unsignedBigInteger('city_id')->nullable()->change();
        });

        Schema::table('players', function (Blueprint $table): void {
            $table->unsignedBigInteger('club_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table): void {
            $table->unsignedBigInteger('club_id')->nullable(false)->change();
        });

        Schema::table('clubs', function (Blueprint $table): void {
            $table->unsignedBigInteger('city_id')->nullable(false)->change();
        });
    }
};
