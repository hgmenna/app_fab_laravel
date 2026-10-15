<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_transfer_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('account_holder');
            $table->string('tax_id', 20);
            $table->string('bank_name')->default('Banco Macro SA');
            $table->string('account_type')->nullable();
            $table->string('account_number')->nullable();
            $table->string('cbu', 22);
            $table->string('alias')->nullable();
            $table->unsignedSmallInteger('expires_in_days')->default(5);
            $table->string('treasury_email')->nullable();
            $table->text('instructions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('bank_transfer_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('club_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('discipline_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('purpose', ['membership', 'tournament_registration']);
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['pending', 'submitted', 'observed', 'approved', 'rejected', 'expired', 'cancelled'])->default('pending');
            $table->date('expires_at')->nullable();
            $table->string('receipt_path')->nullable();
            $table->date('transferred_at')->nullable();
            $table->decimal('transferred_amount', 12, 2)->nullable();
            $table->string('origin_bank')->nullable();
            $table->string('origin_holder')->nullable();
            $table->string('transaction_number')->nullable();
            $table->text('submitter_notes')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('bank_transfer_order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bank_transfer_order_id')->constrained()->cascadeOnDelete();
            $table->nullableMorphs('payable');
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transfer_order_items');
        Schema::dropIfExists('bank_transfer_orders');
        Schema::dropIfExists('bank_transfer_settings');
    }
};
