<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Actions\DropGameClaimAction;
use App\Community\Actions\ExtendGameClaimAction;
use App\Community\Enums\ClaimSpecial;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Models\AchievementSetClaim;
use App\Models\Comment;
use App\Models\User;
use App\Notifications\Achievement\ExpiringClaimNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class ProcessExpiringClaimsAction
{
    public function execute(): void
    {
        $expireThreshold = Carbon::now()->addDays(7);
        $expiringClaims = AchievementSetClaim::activeOrInReview()
            ->whereIn('claim_type', [ClaimType::Primary, ClaimType::Collaboration])
            ->where('finished_at', '>=', Carbon::now())
            ->where('finished_at', '<', $expireThreshold)
            ->with(['game.system', 'user'])
            ->get();

        $cacheKey = "claims:expiring:notified";
        $notificationsSent = Cache::get($cacheKey);

        $systemUser = null;
        $newNotificationsSent = [];
        foreach ($expiringClaims as $claim) {
            if (
                $claim->special_type === ClaimSpecial::ScheduledRelease
                || $claim->status === ClaimStatus::InReview
            ) {
                // A ScheduledRelease claim is a completed claim that cannot be released until some future date.
                // An InReview claim is a potentially completed claim that cannot be released until it's been reviewed.
                // Since the user isn't controlling the release for these, automatically extend them.
                $systemUser ??= User::find(Comment::SYSTEM_USER_ID);
                (new ExtendGameClaimAction())->execute($claim, $systemUser);
                continue;
            }

            if ($claim->claim_type === ClaimType::Collaboration && $systemUser) {
                // Collaboration claim may have been extended by auto-extending the primary claim.
                $claim->refresh();
                if ($claim->finished_at > $expireThreshold) {
                    continue;
                }
            }

            $remaining = $claim->finished_at->diffInHours(Carbon::now(), true);
            if ($remaining < 24) {
                $state = 2;
            } else {
                $state = 1;
            }

            if (($notificationsSent[$claim->id] ?? 0) !== $state && $claim->user) {
                $claim->user->notify(new ExpiringClaimNotification($claim));
            }

            $newNotificationsSent[$claim->id] = $state;
        }

        Cache::put($cacheKey, $newNotificationsSent);

        $expiredClaims = AchievementSetClaim::activeOrInReview()
            ->whereIn('claim_type', [ClaimType::Primary, ClaimType::Collaboration])
            ->where('finished_at', '<', Carbon::now())
            ->with(['game.system', 'user'])
            ->get();
        foreach ($expiredClaims as $claim) {
            $systemUser ??= User::find(Comment::SYSTEM_USER_ID);

            if (
                $claim->special_type === ClaimSpecial::ScheduledRelease
                || $claim->status === ClaimStatus::InReview
            ) {
                // Unexpected, as these should be auto-extended by the logic above, but this
                // handles last-minute changes, and data prior to this functionality being added.
                (new ExtendGameClaimAction())->execute($claim, $systemUser);
            } else {
                (new DropGameClaimAction())->execute($claim, $systemUser);
            }
        }
    }
}
