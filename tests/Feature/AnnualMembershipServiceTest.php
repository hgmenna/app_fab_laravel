<?php

use App\Models\Club;
use App\Models\BankTransferOrder;
use App\Models\BankTransferSetting;
use App\Models\Membership;
use App\Models\Player;
use App\Services\AnnualMembershipService;
use App\Services\BankTransferService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    foreach (['bank_transfer_order_items', 'bank_transfer_orders', 'bank_transfer_settings', 'payments', 'player_memberships', 'memberships', 'players', 'clubs', 'disciplines'] as $table) {
        Schema::dropIfExists($table);
    }

    Schema::create('disciplines', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->timestamps();
    });
    Schema::create('clubs', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
    Schema::create('players', function (Blueprint $table): void {
        $table->id();
        $table->string('first_name');
        $table->string('last_name');
        $table->foreignId('discipline_id');
        $table->foreignId('club_id')->nullable();
        $table->foreignId('category_id')->nullable();
        $table->boolean('is_active')->default(true);
        $table->boolean('is_enabled_to_compete')->default(false);
        $table->timestamps();
    });
    Schema::create('memberships', function (Blueprint $table): void {
        $table->id();
        $table->integer('year');
        $table->foreignId('discipline_id');
        $table->decimal('amount', 10, 2);
        $table->date('due_date')->nullable();
        $table->boolean('active')->default(true);
        $table->text('notes')->nullable();
        $table->timestamps();
    });
    Schema::create('player_memberships', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('player_id');
        $table->foreignId('membership_id');
        $table->foreignId('club_id')->nullable();
        $table->decimal('amount_due', 10, 2);
        $table->decimal('amount_paid', 10, 2)->default(0);
        $table->string('status')->default('pending');
        $table->boolean('enabled_to_compete')->default(false);
        $table->timestamp('paid_at')->nullable();
        $table->timestamps();
    });
    Schema::create('payments', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('player_membership_id');
        $table->string('payer_type');
        $table->unsignedBigInteger('payer_id');
        $table->decimal('amount', 10, 2);
        $table->string('method');
        $table->string('external_reference')->nullable();
        $table->string('status');
        $table->timestamps();
    });
    Schema::create('bank_transfer_settings', function (Blueprint $table): void {
        $table->id();
        $table->string('account_holder');
        $table->string('tax_id');
        $table->string('bank_name');
        $table->string('cbu');
        $table->unsignedSmallInteger('expires_in_days')->default(5);
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
    Schema::create('bank_transfer_orders', function (Blueprint $table): void {
        $table->id();
        $table->string('code')->unique();
        $table->unsignedBigInteger('user_id')->nullable();
        $table->unsignedBigInteger('club_id')->nullable();
        $table->unsignedBigInteger('discipline_id')->nullable();
        $table->string('purpose');
        $table->decimal('amount', 12, 2);
        $table->string('status')->default('pending');
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
        $table->unsignedBigInteger('reviewed_by')->nullable();
        $table->timestamp('reviewed_at')->nullable();
        $table->timestamps();
    });
    Schema::create('bank_transfer_order_items', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('bank_transfer_order_id');
        $table->string('payable_type');
        $table->unsignedBigInteger('payable_id');
        $table->string('description');
        $table->decimal('amount', 12, 2);
        $table->timestamps();
    });

    BankTransferSetting::create([
        'account_holder' => 'Federación Argentina de Billar',
        'tax_id' => '30000000000',
        'bank_name' => 'Banco Macro SA',
        'cbu' => '0000000000000000000000',
        'expires_in_days' => 5,
        'is_active' => true,
    ]);

    DB::table('disciplines')->insert(['id' => 1, 'name' => '5 Quillas', 'created_at' => now(), 'updated_at' => now()]);
    DB::table('clubs')->insert([
        ['id' => 1, 'name' => 'Club anterior', 'created_at' => now(), 'updated_at' => now()],
        ['id' => 2, 'name' => 'Club nuevo', 'created_at' => now(), 'updated_at' => now()],
    ]);
    DB::table('players')->insert([
        ['id' => 1, 'first_name' => 'ANA', 'last_name' => 'UNO', 'discipline_id' => 1, 'club_id' => 1, 'is_active' => true, 'is_enabled_to_compete' => false, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 2, 'first_name' => 'BETO', 'last_name' => 'DOS', 'discipline_id' => 1, 'club_id' => 1, 'is_active' => true, 'is_enabled_to_compete' => true, 'created_at' => now(), 'updated_at' => now()],
    ]);
});

it('creates a pending transfer order and enables the player only after approval', function () {
    $service = app(AnnualMembershipService::class);
    $membership = $service->configure(now()->year, 1, 15000, today()->toDateString());

    $renewed = $service->renew($membership, Player::query()->whereKey(1)->get(), Club::findOrFail(2));

    expect($renewed)->toHaveCount(1)
        ->and($renewed->first()->status)->toBe('pending')
        ->and((int) $renewed->first()->club_id)->toBe(2)
        ->and(Player::findOrFail(1)->is_enabled_to_compete)->toBeFalse()
        ->and(BankTransferOrder::query()->where('status', 'pending')->exists())->toBeTrue();

    $order = BankTransferOrder::query()->firstOrFail();
    $order->update(['status' => 'submitted', 'transferred_amount' => 15000]);
    app(BankTransferService::class)->approve($order);

    expect($renewed->first()->fresh()->status)->toBe('paid')
        ->and(Player::findOrFail(1)->is_enabled_to_compete)->toBeTrue()
        ->and((int) Player::findOrFail(1)->club_id)->toBe(2)
        ->and($renewed->first()->payments()->where('status', 'approved')->exists())->toBeTrue();
});

it('disables only players who did not renew after the discipline closing date', function () {
    $service = app(AnnualMembershipService::class);
    $membership = Membership::create([
        'year' => now()->year,
        'discipline_id' => 1,
        'amount' => 10000,
        'due_date' => today(),
        'active' => true,
    ]);
    $service->renew($membership, Player::query()->whereKey(1)->get(), Club::findOrFail(2));
    $order = BankTransferOrder::query()->firstOrFail();
    $order->update(['status' => 'submitted', 'transferred_amount' => 10000]);
    app(BankTransferService::class)->approve($order);

    expect($service->expireOverdue())->toBe(1)
        ->and(Player::findOrFail(1)->is_enabled_to_compete)->toBeTrue()
        ->and(Player::findOrFail(2)->is_enabled_to_compete)->toBeFalse();
});
