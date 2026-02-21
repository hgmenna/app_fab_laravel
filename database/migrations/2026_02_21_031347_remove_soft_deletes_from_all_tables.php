<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', fn (Blueprint $t) => $t->dropSoftDeletes());
        Schema::table('clubs', fn (Blueprint $t) => $t->dropSoftDeletes());
        Schema::table('tournaments', fn (Blueprint $t) => $t->dropSoftDeletes());
        Schema::table('tournament_registrations', fn (Blueprint $t) => $t->dropSoftDeletes());
        Schema::table('memberships', fn (Blueprint $t) => $t->dropSoftDeletes());
        Schema::table('tournament_types', fn (Blueprint $t) => $t->dropSoftDeletes());
    }

    public function down(): void
    {
        Schema::table('players', fn (Blueprint $t) => $t->softDeletes());
        Schema::table('clubs', fn (Blueprint $t) => $t->softDeletes());
        Schema::table('tournaments', fn (Blueprint $t) => $t->softDeletes());
        Schema::table('tournament_registrations', fn (Blueprint $t) => $t->softDeletes());
        Schema::table('memberships', fn (Blueprint $t) => $t->softDeletes());
        Schema::table('tournament_types', fn (Blueprint $t) => $t->softDeletes());
    }
};


