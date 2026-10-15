<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tournament_registrations', 'payment_status')) {
            return;
        }

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE `tournament_registrations` MODIFY `payment_status` VARCHAR(30) NOT NULL DEFAULT 'pending'");
        }

        DB::table('tournament_registrations')
            ->whereIn('payment_status', ['pendiente', 'unpaid'])
            ->update(['payment_status' => 'pending']);
        DB::table('tournament_registrations')
            ->whereIn('payment_status', ['pagado', 'approved'])
            ->update(['payment_status' => 'paid']);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('tournament_registrations', 'payment_status')) {
            return;
        }

        DB::table('tournament_registrations')
            ->whereNotIn('payment_status', ['pending', 'paid'])
            ->update(['payment_status' => 'pending']);

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE `tournament_registrations` MODIFY `payment_status` ENUM('pending','paid') NOT NULL DEFAULT 'pending'");
        }
    }
};
