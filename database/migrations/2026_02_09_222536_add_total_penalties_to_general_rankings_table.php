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
        Schema::table('general_rankings', function (Blueprint $table) {
            //
            $table->integer('total_penalties')->default(0)->after('total_puntos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('general_rankings', function (Blueprint $table) {
            $table->dropColumn('total_penalties');
        });
    }
};
