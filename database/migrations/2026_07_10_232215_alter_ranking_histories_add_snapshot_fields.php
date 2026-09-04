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
        Schema::table('ranking_histories', function (Blueprint $table) {

            $table->string('last_name')->after('category');

            $table->string('first_name')->after('last_name');

            $table->string('club')->nullable()->after('first_name');

            $table->string('fed')->nullable()->after('club');

            $table->integer('total_puntos')->after('fed');

            $table->string('pos_1')->nullable();
            $table->integer('ptos_1')->nullable();

            $table->string('pos_2')->nullable();
            $table->integer('ptos_2')->nullable();

            $table->string('pos_3')->nullable();
            $table->integer('ptos_3')->nullable();

            $table->string('pos_4')->nullable();
            $table->integer('ptos_4')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
