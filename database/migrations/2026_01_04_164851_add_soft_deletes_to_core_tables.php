<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('clubs', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('tournaments', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('tournament_registrations', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('memberships', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('tournament_types', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('clubs', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('tournament_registrations', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('memberships', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('tournament_types', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }         


};
