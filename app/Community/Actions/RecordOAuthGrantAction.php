<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Models\OAuthGrant;
use App\Models\User;

class RecordOAuthGrantAction
{
    /** @param string[] $scopes */
    public function execute(User $user, string $clientId, array $scopes): OAuthGrant
    {
        $grant = OAuthGrant::query()->firstOrNew([
            'user_id' => $user->id,
            'client_id' => $clientId,
        ]);

        $grant->fill([
            'scopes' => array_values($scopes),
            'first_granted_at' => $grant->first_granted_at ?? now(),
            'revoked_at' => null,
        ])->save();

        return $grant;
    }
}
