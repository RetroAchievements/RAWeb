<?php

use App\Enums\Permissions;
use App\Models\PasswordResetToken;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

function changePassword(string $username, string $password): string
{
    $hashedPassword = Hash::make($password);

    $user = User::whereName($username)->first();

    $user->password = $hashedPassword;
    $user->legacy_salted_password = '';
    $user->saveQuietly();

    PasswordResetToken::where('user_id', $user->id)->delete();

    return $hashedPassword;
}

/*
 * COOKIE
 */

function authenticateFromCookie(
    ?string &$userOut,
    ?int &$permissionsOut,
    ?array &$userDetailsOut = null,
    ?int $minPermissions = null,
): bool {
    $userOut = null;
    $permissionsOut = Permissions::Unregistered;

    // remove legacy cookies
    cookie()->forget('RA_User');
    cookie()->forget('RA_Cookie');
    cookie()->forget('RAPrefs_CSS');

    /** @var ?User $user */
    $user = auth()->user();

    if (!$user) {
        return false;
    }

    $userDetailsOut = $user->toArray();
    $userDetailsOut['isMuted'] = $user->isMuted;
    $userOut = $user->username;
    $permissionsOut = $user->getAttribute('Permissions');

    if ($permissionsOut === Permissions::Banned) {
        return false;
    }

    // validate permissions for the current page if required
    if (isset($minPermissions) && $permissionsOut < $minPermissions) {
        if (request()->wantsJson()) {
            abort(403);
        }

        return false;
    }

    return true;
}

/*
 * WEB API Key
 * TODO replace with passport personal token
 */

function generateAPIKey(string $username): string
{
    $user = User::whereName($username)->first();
    if (!$user || !$user->isEmailVerified()) {
        return '';
    }

    $newKey = Str::random(32);

    $user->web_api_key = $newKey;
    $user->saveQuietly();

    return $newKey;
}
