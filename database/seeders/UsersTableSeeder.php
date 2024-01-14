<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Site\Enums\Permissions;
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

        // if(app()->environment('local')) {
        //     User::factory()->count(50)->create()->each(function ($user) {
        //         // $user->achievements()->save(factory(Achievement::class)->make(rand(0, 1000)));
        //     });
        // }
    }
}
