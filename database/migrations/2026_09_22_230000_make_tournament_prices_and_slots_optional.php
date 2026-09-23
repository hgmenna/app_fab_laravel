<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournament_category_price', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable()->change();
            $table->decimal('price', 10, 2)->nullable()->change();
        });

        Schema::table('tournament_slots', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
            $table->dateTime('starts_at')->nullable()->change();
            $table->unsignedInteger('max_players')->nullable()->change();
        });

        Schema::table('tournament_registrations', function (Blueprint $table) {
            $table->unsignedBigInteger('tournament_slot_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('tournament_category_price')
            ->whereNull('category_id')
            ->orWhereNull('price')
            ->delete();

        DB::table('tournament_slots')
            ->whereNull('starts_at')
            ->orWhereNull('max_players')
            ->delete();

        Schema::table('tournament_category_price', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable(false)->change();
            $table->decimal('price', 10, 2)->nullable(false)->change();
        });

        Schema::table('tournament_slots', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
            $table->dateTime('starts_at')->nullable(false)->change();
            $table->unsignedInteger('max_players')->nullable(false)->change();
        });

        DB::table('tournament_registrations')
            ->whereNull('tournament_slot_id')
            ->delete();

        Schema::table('tournament_registrations', function (Blueprint $table) {
            $table->unsignedBigInteger('tournament_slot_id')->nullable(false)->change();
        });
    }
};
