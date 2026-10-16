<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clubs', function (Blueprint $table): void {
            $table->string('bank_account_holder')->nullable();
            $table->string('bank_tax_id', 20)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_type')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_cbu', 22)->nullable();
            $table->string('bank_alias')->nullable();
            $table->string('bank_treasury_email')->nullable();
            $table->text('bank_transfer_instructions')->nullable();
        });

        Schema::table('tournaments', function (Blueprint $table): void {
            $table->foreignId('organizer_club_id')->nullable()->after('venue_id')->constrained('clubs')->nullOnDelete();
        });

        DB::table('tournaments')->where('venue_type', 'club')->whereNotNull('venue_id')
            ->update(['organizer_club_id' => DB::raw('venue_id')]);

        Schema::create('tournament_contacts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 50);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('tournaments')->join('clubs', 'clubs.id', '=', 'tournaments.organizer_club_id')
            ->whereNotNull('clubs.contact_person')->whereNotNull('clubs.phone')
            ->select('tournaments.id', 'clubs.contact_person', 'clubs.phone')
            ->orderBy('tournaments.id')->chunk(200, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('tournament_contacts')->insert([
                        'tournament_id' => $row->id, 'name' => $row->contact_person,
                        'phone' => $row->phone, 'sort_order' => 0,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            });

        Schema::table('bank_transfer_orders', function (Blueprint $table): void {
            $table->string('beneficiary_type', 20)->nullable();
            $table->string('beneficiary_name')->nullable();
            $table->string('destination_account_holder')->nullable();
            $table->string('destination_tax_id', 20)->nullable();
            $table->string('destination_bank_name')->nullable();
            $table->string('destination_account_type')->nullable();
            $table->string('destination_account_number')->nullable();
            $table->string('destination_cbu', 22)->nullable();
            $table->string('destination_alias')->nullable();
            $table->string('destination_treasury_email')->nullable();
            $table->text('destination_instructions')->nullable();
        });

        $setting = DB::table('bank_transfer_settings')->where('is_active', true)->latest('id')->first();
        if ($setting) {
            DB::table('bank_transfer_orders')->update([
                'beneficiary_type' => 'federation',
                'beneficiary_name' => 'Federación Argentina de Billar',
                'destination_account_holder' => $setting->account_holder,
                'destination_tax_id' => $setting->tax_id,
                'destination_bank_name' => $setting->bank_name,
                'destination_account_type' => $setting->account_type,
                'destination_account_number' => $setting->account_number,
                'destination_cbu' => $setting->cbu,
                'destination_alias' => $setting->alias,
                'destination_treasury_email' => $setting->treasury_email,
                'destination_instructions' => $setting->instructions,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_contacts');
        Schema::table('tournaments', fn (Blueprint $table) => $table->dropConstrainedForeignId('organizer_club_id'));
        Schema::table('bank_transfer_orders', function (Blueprint $table): void {
            $table->dropColumn(['beneficiary_type', 'beneficiary_name', 'destination_account_holder', 'destination_tax_id',
                'destination_bank_name', 'destination_account_type', 'destination_account_number', 'destination_cbu',
                'destination_alias', 'destination_treasury_email', 'destination_instructions']);
        });
        Schema::table('clubs', function (Blueprint $table): void {
            $table->dropColumn(['bank_account_holder', 'bank_tax_id', 'bank_name', 'bank_account_type',
                'bank_account_number', 'bank_cbu', 'bank_alias', 'bank_treasury_email', 'bank_transfer_instructions']);
        });
    }
};
