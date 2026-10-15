<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discipline_user_roles', function (Blueprint $table): void {
            $table->foreignId('club_id')
                ->nullable()
                ->after('role_id')
                ->constrained('clubs')
                ->restrictOnDelete();
        });

        DB::table('roles')->insertOrIgnore([
            'name' => 'dirigente-club',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $roleId = DB::table('roles')->where('name', 'dirigente-club')->where('guard_name', 'web')->value('id');

        $permissions = [
            'ViewAny:Tournament', 'View:Tournament', 'Create:Tournament', 'Update:Tournament',
            'ViewAny:TournamentRegistration', 'View:TournamentRegistration',
            'Create:TournamentRegistration', 'Update:TournamentRegistration',
            'AssignTournamentScore',
            'ViewAny:Player', 'View:Player', 'Create:Player', 'Update:Player',
        ];

        foreach (DB::table('permissions')->whereIn('name', $permissions)->pluck('id') as $permissionId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }
    }

    public function down(): void
    {
        $roleId = DB::table('roles')->where('name', 'dirigente-club')->where('guard_name', 'web')->value('id');

        if ($roleId) {
            DB::table('discipline_user_roles')->where('role_id', $roleId)->delete();
            DB::table('role_has_permissions')->where('role_id', $roleId)->delete();
            DB::table('roles')->where('id', $roleId)->delete();
        }

        Schema::table('discipline_user_roles', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('club_id');
        });
    }
};
