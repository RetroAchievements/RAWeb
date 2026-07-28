<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Models\OAuthGrant;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

class RecordOAuthGrantAction
{
    /** @param string[] $scopes */
    public function execute(User $user, string $clientId, array $scopes): OAuthGrant
    {
        try {
            return $this->record($user, $clientId, $scopes);
        } catch (UniqueConstraintViolationException) {
            /**
             * Concurrent approvals for the same user and client can both miss the
             * firstOrNew() lookup. The loser's insert trips the unique key, and a
             * retry finds the winner's row and updates it instead.
             */
            return $this->record($user, $clientId, $scopes);
        }
    }

    /** @param string[] $scopes */
    private function record(User $user, string $clientId, array $scopes): OAuthGrant
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
