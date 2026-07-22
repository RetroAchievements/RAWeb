<?php

declare(strict_types=1);

namespace App\Community\Controllers\Api;

use App\Community\Actions\RevokeOAuthGrantAction;
use App\Http\Controller;
use App\Models\OAuthGrant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConnectedOAuthApplicationApiController extends Controller
{
    public function destroy(Request $request, string $client, RevokeOAuthGrantAction $revokeOAuthGrant): JsonResponse
    {
        /**
         * Deliberately unpoliced.
         * The grant lookup is already scoped to the caller, so a user can
         * only ever sever their own connection. It also has to keep working
         * even if the OAuth feature flag is disabled, otherwise users would be
         * stuck with connections they cannot revoke.
         */

        /** @var User $user */
        $user = $request->user();

        /** @var OAuthGrant $grant */
        $grant = OAuthGrant::query()
            ->whereBelongsTo($user)
            ->where('client_id', $client)
            ->whereNull('revoked_at')
            ->firstOrFail();

        $revokeOAuthGrant->execute($user, $grant);

        return response()->json(['success' => true]);
    }
}
