<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Permissions;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\Concerns\SeedsUsers;
use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    use SeedsUsers;

    public function run(): void
    {
        foreach (config('roles') as $role) {
            $this->seedUserByUsername($role['name'], ['Permissions' => $role['legacy_role']]);
        }
        $this->seedUserByUsername('verified', ['Permissions' => Permissions::Registered]);
        $this->seedUserByUsername('unverified', ['email_verified_at' => null, 'Permissions' => Permissions::Unregistered]);
        $this->seedUserByUsername('unranked', ['unranked_at' => Carbon::now(), 'Untracked' => true, 'Permissions' => Permissions::Registered]);
        $this->seedUserByUsername('banned', ['banned_at' => Carbon::now(), 'Permissions' => Permissions::Banned]);
        $this->seedUserByUsername('spammer', ['banned_at' => Carbon::now(), 'Permissions' => Permissions::Spam]);

        // add a few developers (including juniors and retired developers)
        User::factory()->count(random_int(10, 30))->make()->each(function (User $user) {
            $user->setAttribute('Permissions', Permissions::Developer);
            $user->assignRole(Role::DEVELOPER);
            $user->save();
        });

        User::factory()->count(random_int(5, 10))->make()->each(function (User $user) {
            $user->setAttribute('Permissions', Permissions::JuniorDeveloper);
            $user->assignRole(Role::DEVELOPER_JUNIOR);
            $user->save();
        });

        User::factory()->count(random_int(2, 5))->make()->each(function (User $user) {
            $user->setAttribute('Permissions', Permissions::Registered);
            $user->assignRole(Role::DEVELOPER_RETIRED);
            $user->save();
        });

        // and a whole bunch of players
        User::factory()->count(random_int(50, 200))->create();
    }
}
