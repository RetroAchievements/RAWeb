<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Enums\CommentableType;
use App\Enums\SetClaimChangeAction;
use App\Models\AchievementSetClaim;
use App\Models\User;
use App\Support\Alerts\SetClaimChangeAlert;
use App\Support\Cache\CacheKey;
use Illuminate\Support\Facades\Cache;

class ExtendGameClaimAction
{
    public function execute(AchievementSetClaim $claim, User $actingUser): void
    {
        $claim->finished_at = $claim->finished_at->addMonths(3);
        $claim->extensions_count++;
        $claim->save();

        Cache::forget(CacheKey::buildUserExpiringClaimsCacheKey($claim->user->username));
        addArticleComment("Server", CommentableType::SetClaim, $claim->game->id, "Claim extended by " . $actingUser->display_name);

        (new SetClaimChangeAlert(game: $claim->game, user: $actingUser, claim: $claim, action: SetClaimChangeAction::Extend))->send();

        $collaborationClaims = $claim->game->achievementSetClaims()
            ->activeOrInReview()
            ->collaborationClaim()
            ->with('user')
            ->get();
        foreach ($collaborationClaims as $collaborationClaim) {
            $collaborationClaim->finished_at = $claim->finished_at;
            $collaborationClaim->extensions_count++;
            $collaborationClaim->save();

            Cache::forget(CacheKey::buildUserExpiringClaimsCacheKey($collaborationClaim->user->username));
            addArticleComment("Server", CommentableType::SetClaim, $claim->game->id,
                $collaborationClaim->user->display_name . "'s collaboration claim extended by " . $actingUser->display_name);
        }
    }
}
