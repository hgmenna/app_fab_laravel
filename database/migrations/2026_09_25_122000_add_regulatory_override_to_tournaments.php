<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->boolean('regulatory_override')->default(false)->after('status');
            $table->text('regulatory_override_reason')->nullable()->after('regulatory_override');
            $table->foreignId('regulatory_override_by')->nullable()->after('regulatory_override_reason')
                ->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamp('regulatory_override_at')->nullable()->after('regulatory_override_by');
        });
    }

    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('regulatory_override_by');
            $table->dropColumn(['regulatory_override', 'regulatory_override_reason', 'regulatory_override_at']);
        });
    }
};
