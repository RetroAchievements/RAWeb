<?php

declare(strict_types=1);

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // In test environments the seeder creates all roles from config.
        if (app()->environment('testing') || !DB::table('auth_roles')->exists()) {
            return;
        }

        $roleId = DB::table('auth_roles')->insertGetId([
            'name' => Role::PLAYTEST_MANAGER,
            'display' => 3,
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Assign the role to the playtesting team lead.
        $user = User::find(229851); // TimeCrush
        if ($user) {
            DB::table('auth_model_roles')->insert([
                'role_id' => $roleId,
                'model_type' => 'user',
                'model_id' => $user->id,
            ]);

            $user->update(['visible_role_id' => $roleId]);
        }
    }

    public function down(): void
    {
        $role = DB::table('auth_roles')
            ->where('name', Role::PLAYTEST_MANAGER)
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
