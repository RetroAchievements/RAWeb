<?php

declare(strict_types=1);

namespace Database\Seeders\Concerns;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
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

        $user = User::factory()->create(array_merge(['username' => $safeUsername], $attributes));
        $user->generateNewConnectToken();

        // set the connect token expiry back a few minutes so we can detect if it gets
        // updated to now + expiry_delay
        $user->connect_token_expires_at = $user->connect_token_expires_at->subMinutes(5);
        $user->save();

        if (!$role) {
            return $user->loadMissing('roles');
        }

        $user->assignRole($role['name']);

        return $user;
    }
}
