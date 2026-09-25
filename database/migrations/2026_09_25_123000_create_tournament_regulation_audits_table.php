<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_regulation_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->nullable()->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->string('operation', 20);
            $table->string('result', 20);
            $table->boolean('overridden')->default(false);
            $table->text('override_reason')->nullable();
            $table->json('tournament_snapshot');
            $table->json('conflicts')->nullable();
            $table->json('technical_details')->nullable();
            $table->timestamps();

            $table->index(['tournament_id', 'created_at']);
            $table->index(['result', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_regulation_audits');
    }
};
