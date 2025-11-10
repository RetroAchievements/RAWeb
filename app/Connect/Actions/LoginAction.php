<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Connect\Support\BaseApiAction;
use App\Enums\Permissions;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginAction extends BaseApiAction
{
    protected string $username;
    protected ?string $password;
    protected ?string $token;

    public function execute(string $username, ?string $password = null, ?string $token = null): array
    {
        $this->username = $username;
        $this->password = $password;
        $this->token = $token;

        return $this->process();
    }

    protected function initialize(Request $request): ?array
    {
        if (!$request->has(['u'])) {
            return $this->missingParameters();
        }
        if (!$request->has(['p']) && !$request->has(['t'])) {
            return $this->missingParameters();
        }

        $this->username = request()->input('u') ?? '';
        $this->password = request()->input('p');
        $this->token = request()->input('t');

        return null;
    }

    protected function process(): array
    {
        if (!empty($this->password)) {
            return $this->authenticateFromPassword();
        } elseif (!empty($this->token)) {
            return $this->authenticateFromToken();
        } else {
            return $this->invalidCredentials();
        }
    }

    private function authenticateFromPassword(): array
    {
        $user = User::whereName($this->username)->first();
        if (!$user) {
            return $this->invalidCredentials();
        }

        // don't let Banned or Spam users log in - treat as if the account was not found
        if ($user->isBanned()) {
            return $this->invalidCredentials();
        }

        $hashedPassword = $user->Password;

        // if the user hasn't logged in for a while, they may still have a salted password, upgrade it
        if (!empty($user->SaltedPass) && mb_strlen($user->SaltedPass) === 32) {
            $pepperedPassword = md5($this->password . config('app.legacy_password_salt'));
            if ($user->SaltedPass !== $pepperedPassword) {
                return $this->invalidCredentials();
            }
            $hashedPassword = changePassword($this->username, $this->password);
        }

        // some protected accounts do not have a password and cannot be used through connect
        if (empty($hashedPassword)) {
            return $this->invalidCredentials();
        }

        // validate the password
        if (!Hash::check($this->password, $hashedPassword)) {
            return $this->invalidCredentials();
        }

        // unregistered users must verify their email first - do this after validating the password
        $permissions = (int) $user->getAttribute('Permissions');
        if ($permissions === Permissions::Unregistered) {
            return $this->accessDenied('Access denied. Please verify your email address.');
        }

        // if the token doesn't exist, is incorrectly formatted, or is expired, generate a new token
        if (!$user->isConnectTokenValid() || $user->isConnectTokenExpired()) {
            $user->generateNewConnectToken();
        }

        return $this->completeLogin($user);
    }

    private function authenticateFromToken(): array
    {
        $user = User::whereName($this->username)->where('appToken', $this->token)->first();
        if (!$user) {
            return $this->invalidCredentials(fromToken: true);
        }

        $permissions = (int) $user->getAttribute('Permissions');
        if ($permissions < Permissions::Registered) {
            // unregistered users must verify their email first
            if ($permissions === Permissions::Unregistered) {
                return $this->accessDenied('Access denied. Please verify your email address.');
            }

            // don't let Banned or Spam users log in - treat as if the account was not found
            return $this->invalidCredentials(fromToken: true);
        }

        // if appToken has expired, generate a new one and force the user to log in again.
        if ($user->isConnectTokenExpired()) {
            $user->generateNewConnectToken();
            $user->saveQuietly();

            return [
                'Success' => false,
                'Status' => 401,
                'Code' => 'expired_token',
                'Error' => 'The access token has expired. Please log in again.',
            ];
        }

        return $this->completeLogin($user);
    }

    private function completeLogin(User $user): array
    {
        // keep the token alive for another two weeks
        $user->extendConnectTokenExpiry();
        $user->saveQuietly();

        $permissions = (int) $user->getAttribute('Permissions');

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

    private function invalidCredentials(bool $fromToken = false): array
    {
        return [
            'Success' => false,
            'Status' => 401,
            'Code' => 'invalid_credentials',
            'Error' => $fromToken ?
                'Invalid user/token combination.' :
                'Invalid user/password combination. Please try again.',
        ];
    }
}
