<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // global seeds
        $this->call(RolesTableSeeder::class);
        $this->call(SystemsTableSeeder::class);
        $this->call(EmulatorsTableSeeder::class);
        $this->call(ReleaseTablesSeeder::class);
        $this->call(ForumTableSeeder::class);
        $this->call(StaticTableSeeder::class);

        // local seeds
        if (app()->environment('local')) {
            $this->call(UsersTableSeeder::class);
            $this->call(GamesTableSeeder::class);
            $this->call(NewsTableSeeder::class);
        }
    }
}
