<?php

declare(strict_types=1);

namespace Database\Seeders\Concerns;

use App\Site\Models\Role;
use App\Site\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

trait SeedsUsers
{
    protected function seedUserByUsername(string $username = 'verified', array $attributes = []): User
    {
        // TODO move to UserFactory

        $safeUsername = str_replace('-', '', Str::title($username));

        // see if username matches a role
        $role = (new Collection(config('roles')))->firstWhere('name', $username);
        if ($role) {
            $safeRoleName = str_replace('-', '', $role['name']);
        }

        $user = User::create(array_merge([
            'EmailAddress' => config('mail.from.address'),
            'email_verified_at' => Carbon::now(),
            'APIKey' => $username . '-secret',
            'Motto' => 'I am ' . $username,
            'RAPoints' => 1000,
            'Password' => Hash::make($safeUsername),
            'TrueRAPoints' => 1010,
            'User' => $safeUsername,
            'display_name' => $safeUsername,
        ], $attributes));

        $user->rollConnectToken();

        // set the connect token expiry back a few minutes so we can detect if it gets
        // updated to now + expiry_delay
        $user->appTokenExpiry = $user->appTokenExpiry->subMinutes(5);
        $user->save();

        if (!$role) {
            return $user->loadMissing('roles');
        }

        if (mb_strpos($safeRoleName, mb_substr(str_replace('-', '', Role::DEVELOPER_LEVEL_1), 0, -1)) === 0) {
            /*
             * cannot have a developer rank without being a developer
             */
            $user->assignRole('developer');
        }
        $user->assignRole($role['name']);

        return $user;
    }
}
