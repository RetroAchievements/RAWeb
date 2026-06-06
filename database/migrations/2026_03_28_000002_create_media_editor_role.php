<?php

declare(strict_types=1);

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // In test environments the seeder creates all roles from config.
        if (app()->environment('testing') || !DB::table('auth_roles')->exists()) {
            return;
        }

        $now = now();

        DB::table('auth_roles')->insert([
            'name' => Role::MEDIA_EDITOR,
            'display' => 5,
            'guard_name' => 'web',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        $role = DB::table('auth_roles')->where('name', Role::MEDIA_EDITOR)->first();

        if ($role) {
            DB::table('users')
                ->where('visible_role_id', $role->id)
                ->update(['visible_role_id' => null]);

            DB::table('auth_model_roles')
                ->where('role_id', $role->id)
                ->delete();

            DB::table('auth_roles')
                ->where('id', $role->id)
                ->delete();
        }
    }
};
