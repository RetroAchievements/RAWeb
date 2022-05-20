<?php

declare(strict_types=1);

namespace Database\Seeders\Concerns;

use App\Site\Models\Role;
use App\Site\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

trait SeedsUsers
{
    protected function seedUserByUsername(string $username = 'verified', array $attributes = []): User
    {
        $safeUsername = '__' . $username;

        // see if username matches a role
        $role = (new Collection(config('roles')))->firstWhere('name', $username);
        if ($role) {
            $safeRoleName = str_replace('-', '', $role['name']);
        }

        $user = User::create(array_merge([
            'email' => config('mail.from.address'),
            'email_verified_at' => Carbon::now(),
            'api_token' => $username . '-secret',
            'motto' => 'I am ' . $username,
            'points_total' => 1000,
            'password' => Hash::make($safeUsername),
            'points_weighted' => 1010,
            'username' => $safeUsername,
            'display_name' => strtoupper($safeUsername),
        ], $attributes));

        $user->rollConnectToken();

        // set the connect token expiry back a few minutes so we can detect if it gets
        // updated to now + expiry_delay
        $user->connect_token_expires_at = $user->connect_token_expires_at->subMinutes(5);
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
