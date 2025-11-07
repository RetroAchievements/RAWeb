<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Connect\Support\BaseAuthenticatedApiAction;
use App\Enums\ClientSupportLevel;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\User;
use App\Platform\Services\UserAgentService;
use App\Platform\Services\VirtualGameIdService;
use Illuminate\Http\Request;

/**
 * This action provides support for the legacy API function used to identify which achievements
 * the user has already unlocked. New clients should use ?r=startsession instead (available
 * since rcheevos 11.0) which serves as a combined request for ?r=postactivity and ?r=unlocks.
 *
 * This endpoint must be maintained indefinitely for backwards compatibility with:
 * - RetroArch versions prior to 1.17.0.
 * - DLL integrations older than 1.3.
 * - Other legacy clients that haven't migrated to rc_client.
 */
class GetPlayerGameUnlocksAction extends BaseAuthenticatedApiAction
{
    protected ?Game $game;
    protected bool $hardcore;

    public function execute(User $user, Game $game, bool $hardcore): array
    {
        $this->user = $user;
        $this->game = $game;
        $this->hardcore = $hardcore;

        return $this->process();
    }

    protected function initialize(Request $request): ?array
    {
        if (!$request->has(['g'])) {
            return $this->missingParameters();
        }

        $this->hardcore = request()->boolean('h', false);

        $gameId = request()->integer('g', 0);
        if (VirtualGameIdService::isVirtualGameId($gameId)) {
            // unlocks aren't tracked for incompatible hashes
            [$gameId, $compatibility] = VirtualGameIdService::decodeVirtualGameId($gameId);

            return $this->emptyResponse($gameId);
        }

        $this->game = Game::find($gameId);
        if (!$this->game) {
            // this action just returns "no unlocks found" for unknown game
            return $this->emptyResponse($gameId);
        }

        return null;
    }

    protected function process(): array
    {
        $response = $this->emptyResponse($this->game->id);

        // split the unlocks into hardcore and non-hardcore
        $userUnlocks = getUserAchievementUnlocksForGame($this->user, $this->game->id);
        if ($this->hardcore) {
            // ignore unlocks for active event achievements the user hasn't earned
            $userUnlocks = reactivateUserEventAchievements($this->user, $userUnlocks);

            // only return the achievements unlocked in hardcore
            $response['UserUnlocks'] = collect($userUnlocks)
                ->filter(fn ($value, $key) => array_key_exists('DateEarnedHardcore', $value))
                ->keys();
        } else {
            $response['UserUnlocks'] = array_keys($userUnlocks);

            // if the user is using an unknown or outdated client, mark the warning
            // achievement as earned in softcore so it only pops in hardcore.
            $userAgentService = new UserAgentService();
            $clientSupportLevel = $userAgentService->getSupportLevel(request()->header('User-Agent'));
            if ($clientSupportLevel !== ClientSupportLevel::Full) {
                // don't allow outdated client popup to appear in softcore mode
                $response['UserUnlocks'][] = Achievement::CLIENT_WARNING_ID;
            }
        }

        return $response;
    }

    private function emptyResponse(int $gameId): array
    {
        return [
            'Success' => true,
            'GameID' => $gameId,
            'HardcoreMode' => $this->hardcore,
            'UserUnlocks' => [],
        ];
    }
}
