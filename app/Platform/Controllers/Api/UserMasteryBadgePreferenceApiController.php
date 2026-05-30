<?php

declare(strict_types=1);

namespace App\Platform\Controllers\Api;

use App\Http\Controller;
use App\Models\Game;
use App\Models\GameBadge;
use App\Models\User;
use App\Platform\Actions\UpdateMasteryBadgePreferenceAction;
use App\Platform\Requests\UpdateMasteryBadgePreferenceRequest;
use Illuminate\Http\JsonResponse;

class UserMasteryBadgePreferenceApiController extends Controller
{
    /**
     * List the badges a user may pick from for a game they have mastered.
     */
    public function index(Game $game): JsonResponse
    {
        /** @var User $user */
        $user = request()->user();

        abort_unless($user->hasMasteredGame($game->id), 403, 'You have not mastered this game.');

        $badges = $game->badges()
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
    public function update(UpdateMasteryBadgePreferenceRequest $request): JsonResponse
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
}
