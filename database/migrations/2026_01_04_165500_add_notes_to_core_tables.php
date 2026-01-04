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
            $table->text('notes')->nullable()->after('photo_path');
        });

        Schema::table('tournaments', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('venue');
        });

        Schema::table('memberships', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('active');
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn('notes');
        });

        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn('notes');
        });

        Schema::table('memberships', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};
