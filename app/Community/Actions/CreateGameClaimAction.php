<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Enums\ArticleType;
use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimSpecial;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Community\Enums\SubscriptionSubjectType;
use App\Models\AchievementSetClaim;
use App\Models\Game;
use App\Models\User;
use App\Support\Cache\CacheKey;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class CreateGameClaimAction
{
    public function execute(Game $game, ?User $currentUser = null): AchievementSetClaim
    {
        $currentUser ??= Auth::user();

        // Devs have 3 months to complete a claim.
        $expiresAt = Carbon::now()->addMonths(3);

        $claimType = ClaimType::Primary;
        $setType = ClaimSetType::NewSet;
        $special = ClaimSpecial::None;

        $primaryClaim = $game->achievementSetClaims()->activeOrInReview()->primaryClaim()->first();
        if ($primaryClaim !== null) {
            if ($primaryClaim->user->is($currentUser)) {
                // renewing claim
                $primaryClaim->Finished = $expiresAt;
                $primaryClaim->Extension++;
                $primaryClaim->save();

                Cache::forget(CacheKey::buildUserExpiringClaimsCacheKey($currentUser->User));
                addArticleComment("Server", ArticleType::SetClaim, $game->ID, "Claim extended by " . $currentUser->display_name);

                $webhookUrl = config('services.discord.webhook.claims');
                if (!empty($webhookUrl)) {
                    $payload = [
                        'username' => 'Claim Bot',
                        'avatar_url' => media_asset('UserPic/QATeam.png'),
                        'content' => route('game.show', $game) . "\n:timer: " .
                                 "Claim extended by " . $currentUser->display_name,
                    ];
                    (new Client())->post($webhookUrl, ['json' => $payload]);
                }

                return $primaryClaim;
            }

            $claimType = ClaimType::Collaboration;
            $setType = $primaryClaim->SetType;
            $special = $primaryClaim->Special;
        }

        if ($game->achievements_published > 0) {
            $setType = ClaimSetType::Revision;
            if (checkIfSoleDeveloper($currentUser, $game->id)) {
                $special = ClaimSpecial::OwnRevision;
            }
        }

        $newClaim = AchievementSetClaim::create([
            'user_id' => $currentUser->id,
            'game_id' => $game->id,
            'ClaimType' => $claimType,
            'SetType' => $setType,
            'Status' => ClaimStatus::Active,
            'Extension' => 0,
            'Special' => $special,
            'Finished' => $expiresAt,
        ]);

        Cache::forget(CacheKey::buildUserExpiringClaimsCacheKey($currentUser->User));

        addArticleComment("Server", ArticleType::SetClaim, $game->ID,
            ClaimType::toString($claimType) . " " . ($setType == ClaimSetType::Revision ? "revision" : "") . " claim made by " . $currentUser->display_name);

        if ($claimType == ClaimType::Primary) {
            // automatically subscribe the user to game wall comments when they make a claim on the game
            updateSubscription(SubscriptionSubjectType::GameWall, $game->ID, $currentUser->id, true);

            // also automatically subscribe the user to the game's official forum topic (if one exists -
            // the "Make Primary Forum Topic and Claim" functionality makes the claim first, but as the
            // author of the primary forum topic they'll be implicitly subscribed).
            if ($game->ForumTopicID && !isUserSubscribedToForumTopic($game->ForumTopicID, $currentUser->id)) {
                updateSubscription(SubscriptionSubjectType::ForumTopic, $game->ForumTopicID, $currentUser->id, true);
            }
        }

        $webhookUrl = config('services.discord.webhook.claims');
        if (!empty($webhookUrl)) {
            $payload = [
                'username' => 'Claim Bot',
                'avatar_url' => media_asset('UserPic/QATeam.png'),
                'content' => route('game.show', $game) . "\n:new: " .
                             ClaimType::toString($claimType) .
                             ($setType === ClaimSetType::Revision ? ' revision' : '') .
                             " claim made by " . $currentUser->displayName,
            ];
            (new Client())->post($webhookUrl, ['json' => $payload]);
        }

        return $newClaim;
    }
}
