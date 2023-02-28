<?php

namespace Database\Seeders;

use Database\Seeders\Legacy\LegacyDatabaseSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // legacy
        $this->call(LegacyDatabaseSeeder::class);

        // global seeds
        $this->call(RolesTableSeeder::class);
        $this->call(SystemsTableSeeder::class);
        $this->call(EmulatorsTableSeeder::class);
        $this->call(ReleaseTablesSeeder::class);
        $this->call(ForumTableSeeder::class);

        // local seeds
        if (app()->environment('local')) {
            $this->call(UsersTableSeeder::class);
            // $this->call(GamesTableSeeder::class);
            // $this->call(NewsTableSeeder::class);
        }
    }
}
