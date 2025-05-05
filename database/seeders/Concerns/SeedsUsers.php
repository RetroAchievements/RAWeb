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

        $user = User::factory()->create(array_merge(['User' => $safeUsername], $attributes));
        $user->rollConnectToken();

        // set the connect token expiry back a few minutes so we can detect if it gets
        // updated to now + expiry_delay
        $user->appTokenExpiry = $user->appTokenExpiry->subMinutes(5);
        $user->save();

        if (!$role) {
            return $user->loadMissing('roles');
        }

        $user->assignRole($role['name']);

        return $user;
    }
}
