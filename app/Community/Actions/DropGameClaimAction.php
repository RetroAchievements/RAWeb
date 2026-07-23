<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Community\Enums\CommentableType;
use App\Enums\SetClaimChangeAction;
use App\Models\AchievementSetClaim;
use App\Models\User;
use App\Platform\Actions\RevalidateMediaContributionBadgeEligibilityAction;
use App\Support\Alerts\SetClaimChangeAlert;
use App\Support\Cache\CacheKey;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class DropGameClaimAction
{
    public function execute(AchievementSetClaim $claim, User $actingUser): void
    {
        $claim->finished_at = Carbon::now();
        $claim->status = ClaimStatus::Dropped;
        $claim->save();

        Cache::forget(CacheKey::buildUserExpiringClaimsCacheKey($claim->user->username));
        (new RevalidateMediaContributionBadgeEligibilityAction())->execute($claim->user);

        // If the primary claim was dropped and there's a collaboration claim, promote it to primary.
        $firstCollabClaim = ($claim->claim_type === ClaimType::Primary) ?
            $claim->game->achievementSetClaims()->active()->orderBy('created_at')->first() : null;

        if ($firstCollabClaim !== null) {
            // NOTE: this doesn't enforce the maximum number of claims for the user being promoted
            // but collaboration claims should already take a claim slot.
            $firstCollabClaim->claim_type = ClaimType::Primary;
            $firstCollabClaim->save();

            Cache::forget(CacheKey::buildUserExpiringClaimsCacheKey($firstCollabClaim->user->username));

            addArticleComment("Server", CommentableType::SetClaim, $claim->game->id, "Primary claim dropped by {$actingUser->display_name}, transferred to {$firstCollabClaim->user->display_name}");
        } else {
            addArticleComment("Server", CommentableType::SetClaim, $claim->game->id, $claim->claim_type->label() . " claim dropped by {$actingUser->display_name}");
        }

        (new SetClaimChangeAlert(game: $claim->game, user: $actingUser, claim: $claim, action: SetClaimChangeAction::Drop))->send();
    }
}
