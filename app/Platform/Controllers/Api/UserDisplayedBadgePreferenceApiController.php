<?php

declare(strict_types=1);

namespace App\Platform\Controllers\Api;

use App\Community\Enums\AwardType;
use App\Http\Controller;
use App\Models\Game;
use App\Models\GameBadge;
use App\Models\PlayerBadge;
use App\Models\User;
use App\Platform\Actions\UpdateMasteryBadgePreferenceAction;
use App\Platform\Requests\UpdateMasteryBadgePreferenceRequest;
use App\Platform\Requests\UpdateMediaContributionTierPreferenceRequest;
use Illuminate\Http\JsonResponse;

class UserDisplayedBadgePreferenceApiController extends Controller
{
    /**
     * List the badges a user may pick from for a game they have mastered.
     */
    public function gameSelectableBadges(Game $game): JsonResponse
    {
        /** @var User $user */
        $user = request()->user();

        abort_unless($user->hasMasteredGame($game->id), 403, 'You have not mastered this game.');

        $badges = $game->badges()
            ->orderByRaw('replaced_at IS NOT NULL')
            ->orderByDesc('became_current_at')
            ->get();

        $selectedSha1 = $user->badgePreferences()->where('game_id', $game->id)->value('sha1');

        $payload = $badges->map(function (GameBadge $badge) use ($selectedSha1): array {
            $isCurrent = $badge->replaced_at === null;

            return [
                'sha1' => $badge->sha1,
                'url' => $badge->badge_url,
                'label' => $isCurrent ? 'Current' : $badge->became_current_at->format('M j, Y'),
                'isCurrent' => $isCurrent,
                'isSelected' => $selectedSha1 === null ? $isCurrent : $badge->sha1 === $selectedSha1,
            ];
        });

        return response()->json(['badges' => $payload]);
    }

    /**
     * Set (or clear) the user's displayed badge for a mastered game.
     */
    public function updateGameBadge(UpdateMasteryBadgePreferenceRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validated();

        /** @var Game $game */
        $game = Game::findOrFail($validated['gameId']);

        $url = (new UpdateMasteryBadgePreferenceAction())->execute(
            $user,
            $game,
            $validated['sha1'] ?? null,
        );

        return response()->json([
            'success' => true,
            'url' => $url,
        ]);
    }

    /**
     * List the mediaContrib tiers a user may pick from.
     */
    public function mediaContributionSelectableTiers(): JsonResponse
    {
        /** @var User $user */
        $user = request()->user();

        $badge = $user->playerBadges()
            ->where('award_type', AwardType::MediaContribution)
            ->first();

        abort_if($badge === null, 404, 'You have not earned a Media Contribution award.');

        $earnedTier = (int) $badge->award_tier;
        $selectedTier = $badge->displayed_tier;

        $tiles = [];
        for ($tier = 0; $tier <= $earnedTier; $tier++) {
            $threshold = PlayerBadge::getBadgeThreshold(AwardType::MediaContribution, $tier);
            $tiles[] = [
                'sha1' => (string) $tier,
                'url' => mediaContributionBadgeUrl($tier),
                'label' => 'Tier ' . ($tier + 1) . " ({$threshold}+)",
                'isCurrent' => $tier === $earnedTier,
                'isSelected' => $tier === $selectedTier,
            ];
        }

        return response()->json(['badges' => $tiles]);
    }

    /**
     * Set (or clear) the user's displayed mediaContrib tier.
     */
    public function updateMediaContributionTier(UpdateMediaContributionTierPreferenceRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $rawTierIndex = $request->validated()['tierIndex'] ?? null;
        $tierIndex = $rawTierIndex === null ? null : (int) $rawTierIndex;

        $badge = $user->playerBadges()
            ->where('award_type', AwardType::MediaContribution)
            ->first();

        abort_if($badge === null, 404, 'You have not earned a Media Contribution award.');

        $earnedTier = (int) $badge->award_tier;

        abort_if(
            $tierIndex !== null && $tierIndex > $earnedTier,
            422,
            'Cannot select a tier higher than your earned tier.'
        );

        $badge->display_award_tier = ($tierIndex === null || $tierIndex === $earnedTier) ? null : $tierIndex;
        $badge->save();

        return response()->json([
            'success' => true,
            'url' => mediaContributionBadgeUrl($badge->displayed_tier),
        ]);
    }
}
