<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disciplines', function (Blueprint $table) {
            $table->string('affiliation_mode', 20)
                ->default('provincial')
                ->after('active');

            $table->foreignId('direct_federation_id')
                ->nullable()
                ->after('affiliation_mode')
                ->constrained('federations')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('disciplines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('direct_federation_id');
            $table->dropColumn('affiliation_mode');
        });
    }
};
