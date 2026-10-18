<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('five_quillas_entries', 'entry_phase_id')) {
            Schema::table('five_quillas_entries', function (Blueprint $table): void {
                $table->foreignId('entry_phase_id')->nullable()->after('source_slot_id')
                    ->constrained('five_quillas_phases')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('five_quillas_entries', 'entry_phase_id')) {
            Schema::table('five_quillas_entries', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('entry_phase_id');
            });
        }
    }
};
