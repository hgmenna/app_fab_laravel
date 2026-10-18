<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('five_quillas_entries', function (Blueprint $table): void {
            $table->unsignedSmallInteger('final_position')->nullable()->after('seed')->index();
        });
    }

    public function down(): void
    {
        Schema::table('five_quillas_entries', function (Blueprint $table): void {
            $table->dropIndex(['final_position']);
            $table->dropColumn('final_position');
        });
    }
};
