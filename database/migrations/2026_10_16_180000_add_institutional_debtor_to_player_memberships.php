<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('player_memberships', function (Blueprint $table): void {
            $table->string('debtor_type', 20)->nullable()->after('club_id');
            $table->unsignedBigInteger('debtor_id')->nullable()->after('debtor_type');
            $table->boolean('institutionally_guaranteed')->default(false)->after('debtor_id');
            $table->index(['debtor_type', 'debtor_id'], 'player_memberships_debtor_index');
        });
    }

    public function down(): void
    {
        Schema::table('player_memberships', function (Blueprint $table): void {
            $table->dropIndex('player_memberships_debtor_index');
            $table->dropColumn(['debtor_type', 'debtor_id', 'institutionally_guaranteed']);
        });
    }
};
