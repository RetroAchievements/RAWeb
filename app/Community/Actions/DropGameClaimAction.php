<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Enums\ArticleType;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Models\AchievementSetClaim;
use App\Models\Game;
use App\Models\User;
use App\Support\Cache\CacheKey;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class DropGameClaimAction
{
    public function execute(Game $game): ?AchievementSetClaim
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        $claim = $game->achievementSetClaims()->active()->where('user_id', $currentUser->id)->first();
        if ($claim !== null) {
            $claim->Finished = Carbon::now();
            $claim->Status = ClaimStatus::Dropped;
            $claim->save();

            Cache::forget(CacheKey::buildUserExpiringClaimsCacheKey($currentUser->User));

            // if the primary claim was dropped and there's a collaboration claim, promote it to primary
            $firstCollabClaim = ($claim->ClaimType === ClaimType::Primary) ?
                $game->achievementSetClaims()->active()->orderBy('Created')->first() : null;

            if ($firstCollabClaim !== null) {
                // NOTE: this doesn't enforce the maximum number of claims for the user being promoted
                //       but collaboration claims should already take a claim slot.
                $firstCollabClaim->ClaimType = ClaimType::Primary;
                $firstCollabClaim->save();

                Cache::forget(CacheKey::buildUserExpiringClaimsCacheKey($firstCollabClaim->user->User));

                addArticleComment("Server", ArticleType::SetClaim, $game->ID, "Primary claim dropped by {$currentUser->User}, transferred to {$firstCollabClaim->user->User}");
            } else {
                addArticleComment("Server", ArticleType::SetClaim, $game->ID, ClaimType::toString($claim->ClaimType) . " claim dropped by {$currentUser->User}");
            }

        }

        return $claim;
    }
}
