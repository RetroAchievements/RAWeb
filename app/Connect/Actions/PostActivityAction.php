<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Connect\Support\BaseAuthenticatedApiAction;
use App\Models\Game;
use App\Platform\Events\PlayerSessionHeartbeat;
use App\Platform\Services\VirtualGameIdService;
use Illuminate\Http\Request;

// NOTE: This function originally allowed posting activity directly to the global feed. That
//       was removed in 2019. This function is still used to start sessions on older clients.
// DEPRECATED: clients should be using startsession instead [added 2023 Jul 11], which serves
//             as a combined request for postactivity and unlocks.
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
