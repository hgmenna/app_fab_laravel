<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_regulation_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discipline_id')->unique()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->boolean('enabled')->default(true);
            $table->decimal('minimum_distance_km', 8, 2)->nullable();
            $table->boolean('check_non_official_distance')->default(true);
            $table->boolean('block_non_official_against_official_same_state')->default(true);
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_regulation_settings');
    }
};
