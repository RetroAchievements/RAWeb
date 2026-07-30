<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Models\User;
use Laravel\Passport\Passport;

class RevokeOAuthTokensAction
{
    /**
     * Revoke every access token issued to a client, along with the refresh
     * tokens hanging off those access tokens. Passing a user narrows the
     * revocation to that user's tokens, leaving other users' tokens alone.
     *
     * Callers are responsible for wrapping this in a transaction.
     */
    public function execute(string $clientId, ?User $user = null): void
    {
        $accessTokens = Passport::token()->newQuery()->where('client_id', $clientId);

        if ($user) {
            $accessTokens->where('user_id', $user->id);
        }

        Passport::refreshToken()->newQuery()
            ->whereIn('access_token_id', (clone $accessTokens)->select('id'))
            ->update(['revoked' => true]);

        $accessTokens->update(['revoked' => true]);
    }
}
