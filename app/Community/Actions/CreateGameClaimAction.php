<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimSpecial;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Community\Enums\CommentableType;
use App\Community\Enums\SubscriptionSubjectType;
use App\Community\Services\SubscriptionService;
use App\Enums\SetClaimChangeAction;
use App\Models\AchievementSetClaim;
use App\Models\Game;
use App\Models\Ticket;
use App\Models\User;
use App\Platform\Actions\RevalidateMediaContributionBadgeEligibilityAction;
use App\Support\Alerts\ClaimWithUnresolvedTicketsAlert;
use App\Support\Alerts\SetClaimChangeAlert;
use App\Support\Cache\CacheKey;
use Carbon\Carbon;
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
                (new ExtendGameClaimAction())->execute($primaryClaim, $currentUser);

                return $primaryClaim;
            }

            $claimType = ClaimType::Collaboration;
            $setType = $primaryClaim->set_type;
            $special = $primaryClaim->special_type;
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
            'claim_type' => $claimType,
            'set_type' => $setType,
            'status' => ClaimStatus::Active,
            'extensions_count' => 0,
            'special_type' => $special,
            'finished_at' => $expiresAt,
        ]);

        (new RevalidateMediaContributionBadgeEligibilityAction())->execute($currentUser);

        Cache::forget(CacheKey::buildUserExpiringClaimsCacheKey($currentUser->username));

        addArticleComment("Server", CommentableType::SetClaim, $game->id,
            $claimType->label() . " " . ($setType === ClaimSetType::Revision ? "revision" : "") . " claim made by " . $currentUser->display_name);

        if ($claimType === ClaimType::Primary) {
            $subscriptionService = new SubscriptionService();

            // automatically subscribe the user to game wall comments when they make a claim on the game
            $subscriptionService->updateSubscription($currentUser, SubscriptionSubjectType::GameWall, $game->id, true);

            // also automatically subscribe the user to the game's official forum topic (if one exists -
            // the "Make Primary Forum Topic and Claim" functionality makes the claim first, but as the
            // author of the primary forum topic they'll be implicitly subscribed).
            if ($game->forum_topic_id && !$subscriptionService->isSubscribed($currentUser, SubscriptionSubjectType::ForumTopic, $game->forum_topic_id)) {
                $subscriptionService->updateSubscription($currentUser, SubscriptionSubjectType::ForumTopic, $game->forum_topic_id, true);
            }
        }

        $this->maybeSendClaimWithUnresolvedTicketsAlert($currentUser, $game, $claimType);

        (new SetClaimChangeAlert(game: $game, user: $currentUser, claim: $newClaim, action: SetClaimChangeAction::Create))->send();

        return $newClaim;
    }

    private function maybeSendClaimWithUnresolvedTicketsAlert(User $currentUser, Game $game, ClaimType $claimType): void
    {
        if ($claimType === ClaimType::Collaboration) {
            return;
        }

        if (!ClaimWithUnresolvedTicketsAlert::webhookUrl()) {
            return;
        }

        $ticketCount = Ticket::forAssignee($currentUser)->awaitingDeveloper()->count();
        if ($ticketCount < 2) { // two or more suggests the developer may be ignoring tickets
            return;
        }

        (new ClaimWithUnresolvedTicketsAlert(
            user: $currentUser,
            game: $game,
            ticketCount: $ticketCount,
        ))->send();
    }
}
