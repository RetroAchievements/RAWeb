<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Connect\Support\BaseAuthenticatedApiAction;
use App\Connect\Support\CanBeDelegated;
use App\Models\Game;
use App\Models\GameHash;
use App\Models\User;
use App\Platform\Events\PlayerSessionHeartbeat;
use Illuminate\Http\Request;

class PingAction extends BaseAuthenticatedApiAction
{
    use CanBeDelegated;

    protected ?Game $game;
    protected string $richPresenceMessage;
    protected ?string $gameHashMd5;
    protected ?bool $hardcore;

    public function execute(User $user, Game $game, string $richPresenceMessage, ?string $gameHashMd5 = null, ?bool $hardcore = null): array
    {
        $this->user = $user;
        $this->game = $game;
        $this->richPresenceMessage = $richPresenceMessage;
        $this->gameHashMd5 = $gameHashMd5;
        $this->hardcore = $hardcore;

        return $this->process();
    }

    protected function initialize(Request $request): ?array
    {
        if (!$request->has(['g'])) {
            return $this->missingParameters();
        }

        $this->game = Game::find(request()->integer('g', 0));
        if (!$this->game) {
            return $this->gameNotFound();
        }

        $result = $this->applyDelegationForGame($request, $this->game);
        if ($result !== null) {
            return $result;
        }

        $this->richPresenceMessage = utf8_sanitize(request()->post('m', ''));
        $this->gameHashMd5 = request()->input('x');

        $hardcore = request()->integer('h');
        if ($hardcore !== null) {
            $this->hardcore = ($hardcore === 1);
        }

        return null;
    }

    protected function process(): array
    {
        $gameHash = null;
        if ($this->gameHashMd5) {
            $gameHash = GameHash::whereMd5($this->gameHashMd5)->first();
            if ($gameHash?->isMultiDiscGameHash()) {
                // ignore game hash if it's a multi-disc game. we don't want the
                // heartbeat generating a new session if the user changes discs.
                $gameHash = null;
            }
        }

        // redirect the heartbeat to the root game.
        $this->game = (new ResolveRootGameFromGameAndGameHashAction())->execute($gameHash, $this->game, $this->user);

        PlayerSessionHeartbeat::dispatch($this->user, $this->game, $this->richPresenceMessage, $gameHash);

        return [
            'Success' => true,
        ];
    }
}
