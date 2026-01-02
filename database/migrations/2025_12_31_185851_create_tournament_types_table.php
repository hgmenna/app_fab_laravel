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
        Schema::create('tournament_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable()->unique();

            $table->boolean('is_official')->default(false);
            $table->boolean('affects_ranking')->default(false);
            $table->boolean('assigns_points')->default(false);

            $table->decimal('score_percentage', 5, 2)->unsigned()->default(100.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournament_types');
    }
};
