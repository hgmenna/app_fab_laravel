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
        Schema::create('tournament_instances', function (Blueprint $table) {
           $table->id();
           $table->string('code')->unique();
           $table->unsignedInteger('points');
           $table->string('description');
           $table->unsignedInteger('instance');
           $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournament_instances');
    }
};
