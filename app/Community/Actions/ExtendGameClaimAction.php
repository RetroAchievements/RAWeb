<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Enums\ArticleType;
use App\Models\AchievementSetClaim;
use App\Models\User;
use App\Support\Cache\CacheKey;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

class ExtendGameClaimAction
{
    public function execute(AchievementSetClaim $claim, User $actingUser): void
    {
        $claim->finished_at = $claim->finished_at->addMonths(3);
        $claim->extensions_count++;
        $claim->save();

        Cache::forget(CacheKey::buildUserExpiringClaimsCacheKey($claim->user->username));
        addArticleComment("Server", ArticleType::SetClaim, $claim->game->id, "Claim extended by " . $actingUser->display_name);

        $webhookUrl = config('services.discord.webhook.claims');
        if (!empty($webhookUrl)) {
            $payload = [
                'username' => 'Claim Bot',
                'avatar_url' => media_asset('UserPic/QATeam.png'),
                'content' => route('game.show', $claim->game) . "\n:timer: " .
                            "Claim extended by " . $actingUser->display_name,
            ];
            (new Client())->post($webhookUrl, ['json' => $payload]);
        }

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
            addArticleComment("Server", ArticleType::SetClaim, $claim->game->id,
                $collaborationClaim->user->display_name . "'s collaboration claim extended by " . $actingUser->display_name);
        }
    }
}
