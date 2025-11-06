<?php

use App\Enums\Permissions;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

function changePassword(string $username, string $password): string
{
    $hashedPassword = Hash::make($password);

    $user = User::whereName($username)->first();

    $user->Password = $hashedPassword;
    $user->SaltedPass = '';
    $user->saveQuietly();

    // TODO: delete password_reset_tokens

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
    $userOut = $user->getAttribute('User');
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
 * TOKEN
 */

/**
 * @param-out int|null $permissionOut
 */
function authenticateFromAppToken(
    ?string &$userOut,
    string $token,
    ?int &$permissionOut,
): bool {
    if (empty($userOut)) {
        return false;
    }
    if (empty($token)) {
        return false;
    }

    /** @var ?User $user */
    $user = auth('connect-token')->user();

    $doesUsernameMatch = $user && (
        strcasecmp($user->User, $userOut) == 0
        || strcasecmp($user->display_name, $userOut) == 0
    );

    if (!$doesUsernameMatch) {
        return false;
    }

    $userOut = $user->User; // always normalize to the username field
    /** @var int|null $permissions */
    $permissions = $user->Permissions;
    $permissionOut = $permissions;

    return true;
}

function generateAppToken(string $username, ?string &$tokenOut): bool
{
    $user = User::whereName($username)->first();
    if (!$user) {
        return false;
    }

    $user->appToken = $tokenOut = newAppToken();
    $user->appTokenExpiry = Carbon::now()->clone()->addDays(14);
    $user->saveQuietly();

    return true;
}

function newAppToken(): string
{
    return Str::random(16);
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

    $user->APIKey = $newKey;
    $user->saveQuietly();

    return $newKey;
}
