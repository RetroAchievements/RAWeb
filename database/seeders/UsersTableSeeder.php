<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Permissions;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\Concerns\SeedsUsers;
use DateTime;
use Faker\Factory as Faker;
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

        $faker = Faker::create();

        // add a few developers (including juniors and retired developers)
        User::factory()->count(rand(10, 30))->make()->each(function (User $user) use ($faker) {
            $user->setAttribute('Permissions', Permissions::Developer);
            $user->assignRole(Role::DEVELOPER);
            $user->created_at = Carbon::parse($faker->dateTimeBetween('-3 years', '-2 months')->format(DateTime::ATOM));
            $user->timestamps = false;
            $user->save();
        });

        User::factory()->count(rand(5, 10))->make()->each(function (User $user) use ($faker) {
            $user->setAttribute('Permissions', Permissions::JuniorDeveloper);
            $user->assignRole(Role::DEVELOPER_JUNIOR);
            $user->created_at = Carbon::parse($faker->dateTimeBetween('-3 years', '-2 days')->format(DateTime::ATOM));
            $user->timestamps = false;
            $user->save();
        });

        User::factory()->count(rand(2, 5))->make()->each(function (User $user) use ($faker) {
            $user->setAttribute('Permissions', Permissions::Registered);
            $user->assignRole(Role::DEVELOPER_RETIRED);
            $user->created_at = Carbon::parse($faker->dateTimeBetween('-3 years', '-6 months')->format(DateTime::ATOM));
            $user->timestamps = false;
            $user->save();
        });

        // and a bunch of players
        User::factory()->count(rand(20, 100))->make()->each(function (User $user) use ($faker) {
            $user->created_at = Carbon::parse($faker->dateTimeBetween('-3 years', '-2 hours')->format(DateTime::ATOM));
            $user->timestamps = false;
            $user->save();
        });
    }
}
