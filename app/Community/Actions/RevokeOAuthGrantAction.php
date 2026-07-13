<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Models\OAuthGrant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RevokeOAuthGrantAction
{
    /**
     * Sever a single user's connection to an application. The application itself
     * stays active for everyone else who has authorized it.
     */
    public function execute(User $user, OAuthGrant $grant): void
    {
        DB::transaction(function () use ($user, $grant): void {
            (new RevokeOAuthTokensAction())->execute($grant->client_id, $user);

            $grant->update(['revoked_at' => now()]);
        });
    }
}
