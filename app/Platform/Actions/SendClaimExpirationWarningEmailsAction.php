<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Mail\ExpiringClaimMail;
use App\Models\AchievementSetClaim;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class SendClaimExpirationWarningEmailsAction
{
    public function execute(): void
    {
        $expiringClaims = AchievementSetClaim::query()
            ->with(['game.system', 'user'])
            ->whereIn('ClaimType', [ClaimType::Primary, ClaimType::Collaboration])
            ->whereIn('Status', [ClaimStatus::Active, ClaimStatus::InReview])
            ->where('Finished', '>=', Carbon::now())
            ->where('Finished', '<', Carbon::now()->addDays(7))
            ->get();

        $cacheKey = "claims:expiring:notified";
        $notificationsSent = Cache::get($cacheKey);

        $newNotificationsSent = [];
        foreach ($expiringClaims as $claim) {
            $remaining = $claim->Finished->diffInHours(Carbon::now(), true);
            if ($remaining < 24) {
                $state = 2;
            } else {
                $state = 1;
            }

            if (($notificationsSent[$claim->ID] ?? 0) !== $state && $claim->user) {
                Mail::to($claim->user)->queue(new ExpiringClaimMail($claim));
            }

            $newNotificationsSent[$claim->ID] = $state;
        }

        Cache::put($cacheKey, $newNotificationsSent);
    }
}
