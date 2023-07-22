<?php

use App\Community\Enums\ActivityType;
use App\Site\Enums\Permissions;
use App\Site\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

function authenticateForConnect(string $username, ?string $pass = null, ?string $token = null): array
{
    $user = null;

    $passwordProvided = (isset($pass) && mb_strlen($pass) >= 1);
    $tokenProvided = (isset($token) && mb_strlen($token) >= 1);
    if ($passwordProvided) {
        // Password provided, validate it
        if (authenticateFromPassword($username, $pass)) {
            $user = User::firstWhere('User', $username);
        }

        $tokenProvided = false; // ignore token if provided
    } elseif ($tokenProvided) {
        // Token provided, look for match
        $user = User::where('User', $username)->where('appToken', $token)->first();
    }

    if (!$user) {
        return [
            'Success' => false,
            'Status' => 401,
            'Code' => 'invalid_credentials',
            'Error' => $tokenProvided ?
                'Invalid User/Token combination. Please try again.' :
                'Invalid User/Password combination. Please try again.',
        ];
    }

    if ($tokenProvided) {
        if ($user->appTokenExpiry && $user->appTokenExpiry < Carbon::now()) {
            // appToken has expired. Generate a new one and force the user to log in again.
            $user->appToken = newAppToken();
            $user->appTokenExpiry = Carbon::now()->clone()->addDays(14);
            $user->save();

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
    $user->timestamps = false;
    $user->save();

    postActivity($user, ActivityType::Login);

    return [
        'Success' => true,
        'User' => $user->User,
        'Token' => $user->appToken,
        'Score' => $user->RAPoints,
        'SoftcoreScore' => $user->RASoftcorePoints,
        'Messages' => GetMessageCount($user, $totalMessageCount),
        'Permissions' => $user->Permissions,
        'AccountType' => Permissions::toString((int) $user->getAttribute('Permissions')),
    ];
}

function authenticateFromPassword(string &$username, string $pass): bool
{
    if (!isValidUsername($username)) {
        return false;
    }

    // use raw query to access non-visible fields
    $query = "SELECT ID, User, Password, SaltedPass, Permissions FROM UserAccounts WHERE User=:user";
    $row = legacyDbFetch($query, ['user' => $username]);
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
        $pepperedPassword = md5($pass . config('app.legacy_password_salt'));
        if ($row['SaltedPass'] !== $pepperedPassword) {
            return false;
        }
        $hashedPassword = migratePassword($username, $pass);
    }

    // validate the password
    if (!password_verify($pass, $hashedPassword)) {
        return false;
    }

    // do the login
    Auth::loginUsingId($row['ID']);

    // case-correct the username
    $username = $row['User'];

    return true;
}

function changePassword(string $user, string $pass): bool
{
    sanitize_sql_inputs($user);

    $hashedPassword = hashPassword($pass);
    $query = "UPDATE UserAccounts SET Password='$hashedPassword', SaltedPass='', Updated=NOW() WHERE user='$user'";
    if (s_mysql_query($query) === false) {
        log_sql_fail();

        return false;
    }

    return true;
}

function hashPassword(string $pass): string
{
    $algorithm = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;

    return password_hash($pass, $algorithm, [
        'memory_cost' => 1024,
        'threads' => 2,
        'time' => 1,
    ]);
}

function migratePassword(string $username, string $pass): string
{
    $hashedPassword = hashPassword($pass);
    legacyDbStatement("UPDATE UserAccounts SET Password=:hashedPassword, SaltedPass='' WHERE User=:user",
        ['hashedPassword' => $hashedPassword, 'user' => $username]);

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
    $userOut = $user->getAttribute('User');
    $permissionsOut = $user->getAttribute('Permissions');

    if ($permissionsOut === Permissions::Banned) {
        return false;
    }

    // valid active account. update the last activity timestamp
    $user->LastLogin = Carbon::now();
    $user->timestamps = false;
    $user->save();

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
    if (!isValidUsername($userOut)) {
        return false;
    }
    if (empty($token)) {
        return false;
    }

    /** @var ?User $user */
    $user = auth('connect-token')->user();

    if (!$user) {
        return false;
    }

    $userOut = $user->User;
    $permissionOut = $user->Permissions;

    return true;
}

function generateAppToken(string $username, ?string &$tokenOut): bool
{
    $user = User::firstWhere('User', $username);
    if (!$user) {
        return false;
    }

    $user->appToken = $tokenOut = newAppToken();
    $user->appTokenExpiry = Carbon::now()->clone()->addDays(14);
    $user->save();

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

function generateAPIKey(string $user): string
{
    sanitize_sql_inputs($user);

    if (!getAccountDetails($user, $userData)) {
        return "";
    }

    if ($userData['Permissions'] < Permissions::Registered) {
        return "";
    }

    $newKey = Str::random(32);

    $query = "UPDATE UserAccounts AS ua
              SET ua.APIKey='$newKey', Updated=NOW()
              WHERE ua.User = '$user'";

    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        return "";
    }

    return $newKey;
}
