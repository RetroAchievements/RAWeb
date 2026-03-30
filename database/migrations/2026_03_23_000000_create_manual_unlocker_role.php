<?php

declare(strict_types=1);

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // In test environments the seeder creates all roles from config, also don't create if database is not seeded
        if (app()->environment('testing') || !DB::table('auth_roles')->exists()) {
            return;
        }

        DB::table('auth_roles')->insert([
            'name' => Role::MANUAL_UNLOCKER,
            'display' => 3,
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $role = DB::table('auth_roles')
            ->where('name', Role::MANUAL_UNLOCKER)
            ->first();

        if ($role) {
            // Reset visible_role_id for any users displaying this role.
            $affectedUserIds = DB::table('users')
                ->where('visible_role_id', $role->id)
                ->pluck('id');

            foreach ($affectedUserIds as $userId) {
                $firstDisplayableRoleId = DB::table('auth_model_roles')
                    ->join('auth_roles', 'auth_roles.id', '=', 'auth_model_roles.role_id')
                    ->where('auth_model_roles.model_id', $userId)
                    ->where('auth_model_roles.model_type', 'user')
                    ->where('auth_roles.display', '>', 0)
                    ->where('auth_roles.id', '!=', $role->id)
                    ->value('auth_roles.id');

                DB::table('users')
                    ->where('id', $userId)
                    ->update(['visible_role_id' => $firstDisplayableRoleId]);
            }

            DB::table('auth_model_roles')
                ->where('role_id', $role->id)
                ->delete();

            DB::table('auth_roles')
                ->where('id', $role->id)
                ->delete();
        }
    }
};
