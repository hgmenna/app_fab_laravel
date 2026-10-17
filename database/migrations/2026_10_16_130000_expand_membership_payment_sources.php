<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('payer_type', 50)->change();
            $table->string('method', 50)->change();
            $table->string('manual_method', 50)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
        });

        Schema::table('bank_transfer_orders', function (Blueprint $table): void {
            $table->string('payer_type', 50)->nullable();
            $table->unsignedBigInteger('payer_id')->nullable();
            $table->string('payer_name')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('bank_transfer_orders', fn (Blueprint $table) => $table->dropColumn(['payer_type', 'payer_id', 'payer_name']));
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('recorded_by');
            $table->dropColumn(['manual_method', 'notes', 'paid_at']);
        });
        Schema::table('payments', function (Blueprint $table): void {
            $table->enum('payer_type', ['player', 'club'])->change();
            $table->enum('method', ['pluspagos', 'mercadopago', 'manual'])->change();
        });
    }
};
