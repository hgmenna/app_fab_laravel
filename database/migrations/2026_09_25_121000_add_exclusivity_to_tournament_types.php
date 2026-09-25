<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournament_types', function (Blueprint $table) {
            $table->boolean('exclusive_during_dates')->default(false)->after('is_official');
        });
    }

    public function down(): void
    {
        Schema::table('tournament_types', function (Blueprint $table) {
            $table->dropColumn('exclusive_during_dates');
        });
    }
};
