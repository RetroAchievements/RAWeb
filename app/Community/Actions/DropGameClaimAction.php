<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Enums\ArticleType;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Models\AchievementSetClaim;
use App\Models\User;
use App\Support\Cache\CacheKey;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

class DropGameClaimAction
{
    public function execute(AchievementSetClaim $claim, User $actingUser): void
    {
        $claim->Finished = Carbon::now();
        $claim->Status = ClaimStatus::Dropped;
        $claim->save();

        Cache::forget(CacheKey::buildUserExpiringClaimsCacheKey($claim->user->User));

        // if the primary claim was dropped and there's a collaboration claim, promote it to primary
        $firstCollabClaim = ($claim->ClaimType === ClaimType::Primary) ?
            $claim->game->achievementSetClaims()->active()->orderBy('Created')->first() : null;

        if ($firstCollabClaim !== null) {
            // NOTE: this doesn't enforce the maximum number of claims for the user being promoted
            //       but collaboration claims should already take a claim slot.
            $firstCollabClaim->ClaimType = ClaimType::Primary;
            $firstCollabClaim->save();

            Cache::forget(CacheKey::buildUserExpiringClaimsCacheKey($firstCollabClaim->user->User));

            addArticleComment("Server", ArticleType::SetClaim, $claim->game->ID, "Primary claim dropped by {$actingUser->display_name}, transferred to {$firstCollabClaim->user->display_name}");
        } else {
            addArticleComment("Server", ArticleType::SetClaim, $claim->game->ID, ClaimType::toString($claim->ClaimType) . " claim dropped by {$actingUser->display_name}");
        }

        $webhookUrl = config('services.discord.webhook.claims');
        if (!empty($webhookUrl)) {
            $payload = [
                'username' => 'Claim Bot',
                'avatar_url' => media_asset('UserPic/QATeam.png'),
                'content' => route('game.show', $claim->game) . "\n:no_entry_sign: " .
                                ClaimType::toString($claim->ClaimType) . " claim dropped by " . $actingUser->display_name,
            ];
            (new Client())->post($webhookUrl, ['json' => $payload]);
        }
    }
}
