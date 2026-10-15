<?php

use App\Models\Club;
use App\Models\Membership;
use App\Models\Player;
use App\Services\AnnualMembershipService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    foreach (['payments', 'player_memberships', 'memberships', 'players', 'clubs', 'disciplines'] as $table) {
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

it('renews players in bulk and stores the club represented that year', function () {
    $service = app(AnnualMembershipService::class);
    $membership = $service->configure(now()->year, 1, 15000, today()->toDateString());

    $renewed = $service->renew($membership, Player::query()->whereKey(1)->get(), Club::findOrFail(2));

    expect($renewed)->toHaveCount(1)
        ->and($renewed->first()->status)->toBe('paid')
        ->and((int) $renewed->first()->club_id)->toBe(2)
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

    expect($service->expireOverdue())->toBe(1)
        ->and(Player::findOrFail(1)->is_enabled_to_compete)->toBeTrue()
        ->and(Player::findOrFail(2)->is_enabled_to_compete)->toBeFalse();
});
