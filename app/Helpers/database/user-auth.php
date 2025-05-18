<?php

use App\Enums\Permissions;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

function authenticateForConnect(?string $username, ?string $pass = null, ?string $token = null): array
{
    if (!$username) {
        return [
            'Success' => false,
            'Status' => 401,
            'Code' => 'invalid_credentials',
            'Error' => 'Invalid username. Please try again.',
        ];
    }

    $user = null;

    $passwordProvided = (isset($pass) && mb_strlen($pass) >= 1);
    $tokenProvided = (isset($token) && mb_strlen($token) >= 1);
    if ($passwordProvided) {
        // Password provided, validate it
        if (authenticateFromPassword($username, $pass)) {
            $user = User::whereName($username)->first();
        }

        $tokenProvided = false; // ignore token if provided
    } elseif ($tokenProvided) {
        // Token provided, look for match
        $user = User::whereName($username)->where('appToken', $token)->first();
    }

    if (!$user) {
        return [
            'Success' => false,
            'Status' => 401,
            'Code' => 'invalid_credentials',
            'Error' => $tokenProvided ?
                'Invalid user/token combination.' :
                'Invalid user/password combination. Please try again.',
        ];
    }

    $permissions = (int) $user->getAttribute('Permissions');
    if ($permissions < Permissions::Registered) {
        return [
            'Success' => false,
            'Status' => 403,
            'Code' => 'access_denied',
            'Error' => ($permissions === Permissions::Unregistered) ?
                'Access denied. Please verify your email address.' :
                'Access denied.',
        ];
    }

    if ($tokenProvided) {
        if ($user->appTokenExpiry && $user->appTokenExpiry < Carbon::now()) {
            // appToken has expired. Generate a new one and force the user to log in again.
            $user->appToken = newAppToken();
            $user->appTokenExpiry = Carbon::now()->clone()->addDays(14);
            $user->saveQuietly();

            return [
                'Success' => false,
                'Status' => 401,
                'Code' => 'expired_token',
                'Error' => 'The access token has expired. Please log in again.',
            ];
        }
    } elseif (mb_strlen($user->appToken) !== 16) {
        // login via password, token doesn't exist or is incorrectly formatted. generate new token
        $user->appToken = newAppToken();
    }

    // update appTokenExpiry
    $user->appTokenExpiry = Carbon::now()->clone()->addDays(14);
    $user->saveQuietly();

    return [
        'Success' => true,
        'User' => $user->display_name,
        'AvatarUrl' => $user->avatar_url,
        'Token' => $user->appToken,
        'Score' => $user->RAPoints,
        'SoftcoreScore' => $user->RASoftcorePoints,
        'Messages' => $user->UnreadMessageCount ?? 0,
        'Permissions' => $permissions,
        'AccountType' => Permissions::toString($permissions),
    ];
}

function authenticateFromPassword(string &$username, string $password): bool
{
    if (!isValidUsername($username)) {
        return false;
    }

    // use raw query to access non-visible fields
    $query = "SELECT ID, User, Password, SaltedPass, Permissions 
        FROM UserAccounts 
        WHERE (User = :user OR display_name = :user2) 
        AND Deleted IS NULL";
    $row = legacyDbFetch($query, ['user' => $username, 'user2' => $username]);
    if (!$row) {
        return false;
    }

    // don't let Banned or Spam users log in
    if ($row['Permissions'] < Permissions::Unregistered) {
        return false;
    }

    $hashedPassword = $row['Password'];

    // if the user hasn't logged in for a while, they may still have a salted password, upgrade it
    if (mb_strlen($row['SaltedPass']) === 32) {
        $pepperedPassword = md5($password . config('app.legacy_password_salt'));
        if ($row['SaltedPass'] !== $pepperedPassword) {
            return false;
        }
        $hashedPassword = changePassword($username, $password);
    }

    // some protected accounts do not have a password anymore
    if (empty($hashedPassword)) {
        return false;
    }

    // validate the password
    if (!Hash::check($password, $hashedPassword)) {
        return false;
    }

    // do the login
    Auth::loginUsingId($row['ID']);

    // case-correct the username
    $username = $row['User'];

    return true;
}

function changePassword(string $username, string $password): string
{
    $hashedPassword = Hash::make($password);

    $user = User::whereName($username)->first();

    $user->Password = $hashedPassword;
    $user->SaltedPass = '';
    $user->PasswordResetToken = '';
    $user->saveQuietly();

    return $hashedPassword;
}

/*
 * COOKIE
 */

function authenticateFromCookie(
    ?string &$userOut,
    ?int &$permissionsOut,
    ?array &$userDetailsOut = null,
    ?int $minPermissions = null
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

function authenticateFromAppToken(
    ?string &$userOut,
    string $token,
    ?int &$permissionOut
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
    $permissionOut = $user->Permissions;

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
