<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $roleId = DB::table('roles')
            ->where('name', 'dirigente-club')
            ->where('guard_name', 'web')
            ->value('id');
        $permissionId = DB::table('permissions')
            ->where('name', 'AssignTournamentScore')
            ->where('guard_name', 'web')
            ->value('id');

        if ($roleId && $permissionId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }
    }

    public function down(): void
    {
        $roleId = DB::table('roles')->where('name', 'dirigente-club')->where('guard_name', 'web')->value('id');
        $permissionId = DB::table('permissions')->where('name', 'AssignTournamentScore')->where('guard_name', 'web')->value('id');

        if ($roleId && $permissionId) {
            DB::table('role_has_permissions')
                ->where('role_id', $roleId)
                ->where('permission_id', $permissionId)
                ->delete();
        }
    }
};
