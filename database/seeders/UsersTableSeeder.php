<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Site\Enums\Permissions;
use App\Site\Models\User;
use Carbon\Carbon;
use Database\Seeders\Concerns\SeedsUsers;
use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    use SeedsUsers;

    public function run(): void
    {
        if (User::find(1_000_001)) {
            return;
        }

        $id = 1_000_000;

        /*
         * offset role test users by 1000000
         */
        foreach (config('roles') as $role) {
            $this->seedUserByUsername($role['name'], ['id' => ++$id, 'Permissions' => $role['legacy_role']]);
        }
        $this->seedUserByUsername('verified', ['id' => ++$id, 'Permissions' => Permissions::Registered]);
        $this->seedUserByUsername('unverified', ['id' => ++$id, 'email_verified_at' => null, 'Permissions' => Permissions::Unregistered]);
        $this->seedUserByUsername('unranked', ['id' => ++$id, 'unranked_at' => Carbon::now(), 'Untracked' => true, 'Permissions' => Permissions::Registered]);
        $this->seedUserByUsername('banned', ['id' => ++$id, 'banned_at' => Carbon::now(), 'Permissions' => Permissions::Banned]);
        $this->seedUserByUsername('spammer', ['id' => ++$id, 'banned_at' => Carbon::now(), 'Permissions' => Permissions::Spam]);

        // if(app()->environment('local')) {
        //     User::factory()->count(50)->create()->each(function ($user) {
        //         // $user->achievements()->save(factory(Achievement::class)->make(rand(0, 1000)));
        //     });
        // }
    }
}
