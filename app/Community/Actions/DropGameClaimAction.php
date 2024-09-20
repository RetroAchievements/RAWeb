<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimSpecial;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Models\AchievementSetClaim;
use App\Models\Game;
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

            // if there's a collaboration claim, promote it to primary
            $firstCollabClaim = $game->achievementSetClaims()->active()->orderBy('Created')->first();
            if ($firstCollabClaim !== null) {
                $firstCollabClaim->ClaimType = ClaimType::Primary;
                $firstCollabClaim->save();

                Cache::forget(CacheKey::buildUserExpiringClaimsCacheKey($firstCollabClaim->user->User));
            }
        }

        return $claim;
    }
}
