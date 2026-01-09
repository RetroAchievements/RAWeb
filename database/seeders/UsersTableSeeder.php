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
        $this->seedUserByUsername('unranked', ['unranked_at' => Carbon::now(), 'Permissions' => Permissions::Registered]);
        $this->seedUserByUsername('banned', ['banned_at' => Carbon::now(), 'Permissions' => Permissions::Banned]);
        $this->seedUserByUsername('spammer', ['banned_at' => Carbon::now(), 'Permissions' => Permissions::Spam]);

        // set the password for all users to their username
        User::all()->each(function (User $user) {
            $this->prepareUser($user);
            $user->saveQuietly();
        });

        // add a few developers (including juniors and retired developers)
        User::factory()->count(rand(10, 20))->make()->each(function (User $user) {
            $user->username = $user->display_name = $this->generateUsername();
            $this->prepareUser($user);
            $user->setAttribute('Permissions', Permissions::Developer);
            $user->assignRole(Role::DEVELOPER);
            $user->save();
        });

        User::factory()->count(rand(5, 10))->make()->each(function (User $user) {
            $user->username = $user->display_name = $this->generateUsername();
            $this->prepareUser($user);
            $user->setAttribute('Permissions', Permissions::JuniorDeveloper);
            $user->assignRole(Role::DEVELOPER_JUNIOR);
            $user->save();
        });

        User::factory()->count(rand(2, 5))->make()->each(function (User $user) {
            $user->username = $user->display_name = $this->generateUsername();
            $this->prepareUser($user);
            $user->setAttribute('Permissions', Permissions::Registered);
            $user->assignRole(Role::DEVELOPER_RETIRED);
            $user->save();
        });

        // and a bunch of players
        User::factory()->count(rand(50, 100))->make()->each(function (User $user) {
            $user->username = $user->display_name = $this->generateUsername();
            $this->prepareUser($user);
            $user->save();
        });
    }

    private function prepareUser(User &$user): void
    {
        $faker = Faker::create();
        $salt = config('app.legacy_password_salt');

        $user->legacy_salted_password = md5($user->username . $salt);
        $user->points_hardcore = $user->points = 0; // factory seeds a user with hardcore points
        $user->created_at = Carbon::parse($faker->dateTimeBetween('-3 years', '-6 months')->format(DateTime::ATOM));
        $user->timestamps = false;
    }

    private function generateUsername(): string
    {
        $stop = false;
        $username = '';
        do {
            switch (rand(0, 10)) {
                case 0:
                    if ($username === '') {
                        $username = ucfirst(fake()->word());
                    } else {
                        $username .= fake()->word();
                    }
                    break;
                case 1:
                    $username .= fake()->word();
                    break;
                case 2:
                case 3:
                case 4:
                case 5:
                case 6:
                    $username .= ucfirst(fake()->word());
                    break;
                case 7:
                    $username .= chr(rand(65, 90)); // random uppercase letter
                    break;
                case 8:
                    $username .= chr(rand(97, 122)); // random lowercase letter
                    break;
                case 9:
                    if ($username !== '') { // username cannot start with digit
                        $username .= chr(rand(48, 57)); // random digit
                    }
                    break;
                case 10:
                    if ($username !== '') { // username cannot start with digit
                        $username .= strval(rand(1, 500)); // random numeric suffix
                        $stop = true;
                    }
                    break;
            }

            $len = strlen($username);
            if ($len >= 12 || ($len > 4 && rand(0, 12 - $len) === 0)) {
                if ($len > 20) {
                    $username = substr($username, 0, 20);
                }
                break;
            }
        } while (!$stop);

        if (User::where('username', $username)->exists()) {
            return $this->generateUsername();
        }

        return $username;
    }
}
