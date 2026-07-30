<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Models\OAuthClient;
use Illuminate\Support\Facades\DB;

class DeactivateOAuthClientAction
{
    /**
     * Retire an application for everyone: every token it was issued stops working,
     * every user's grant is severed, and it can no longer be authorized.
     *
     * This should be treated as an emergency kill switch for a 3rd party OAuth app.
     */
    public function execute(OAuthClient $client): void
    {
        DB::transaction(function () use ($client): void {
            (new RevokeOAuthTokensAction())->execute((string) $client->id);

            $client->grants()->whereNull('revoked_at')->update(['revoked_at' => now()]);
            $client->forceFill(['revoked' => true])->save();
        });
    }
}
