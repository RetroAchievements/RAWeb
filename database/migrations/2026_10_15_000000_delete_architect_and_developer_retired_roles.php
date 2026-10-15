<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('auth_roles')
            ->whereIn('name', ['architect', 'developer-retired'])
            ->delete();
    }

    public function down(): void
    {
        if (app()->environment('testing') || !DB::table('auth_roles')->exists()) {
            return;
        }

        $now = now();

        DB::table('auth_roles')->insert([
            ['name' => 'architect', 'display' => 1, 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'developer-retired', 'display' => 5, 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
};
