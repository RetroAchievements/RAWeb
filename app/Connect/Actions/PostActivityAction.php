<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Connect\Support\BaseAuthenticatedApiAction;
use App\Models\Game;
use App\Platform\Events\PlayerSessionHeartbeat;
use App\Platform\Services\VirtualGameIdService;
use Illuminate\Http\Request;

/**
 * This action provides support for the legacy API function used to initialize client sessions.
 * New clients should use ?r=startsession instead (available since rcheevos 11.0) which
 * serves as a combined request for ?r=postactivity and ?r=unlocks.
 *
 * This originally supported posting activity directly to the global feed (removed in 2019).
 * Now it only processes StartedPlaying (activity type 3) to start sessions for older clients.
 *
 * This endpoint must be maintained indefinitely for backwards compatibility with:
 * - RetroArch versions prior to 1.17.0.
 * - DLL integrations older than 1.3.
 * - Other legacy clients that haven't migrated to rc_client.
 */
class PostActivityAction extends BaseAuthenticatedApiAction
{
    protected int $gameId;

    public function execute(int $gameId): array
    {
        $this->gameId = $gameId;

        return $this->process();
    }

    protected function initialize(Request $request): ?array
    {
        if (!$request->has(['a', 'm'])) {
            return $this->missingParameters();
        }

        // Only activity type 3 (StartedPlaying) is supported.
        // We could return invalidParameter here, but historically any other value returned access denied.
        if (request()->integer('a') !== 3) {
            return $this->accessDenied();
        }

        $this->gameId = request()->integer('m', 0);

        return null;
    }

    protected function process(): array
    {
        if (VirtualGameIdService::isVirtualGameId($this->gameId)) {
            [$this->gameId, $compatibility] = VirtualGameIdService::decodeVirtualGameId($this->gameId);
        }

        $game = Game::find($this->gameId);
        if (!$game) {
            return $this->gameNotFound();
        }

        PlayerSessionHeartbeat::dispatch($this->user, $game);

        return [
            'Success' => true,
        ];
    }
}
