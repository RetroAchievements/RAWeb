<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Connect\Support\BaseAuthenticatedApiAction;
use App\Models\Game;
use App\Models\User;
use App\Platform\Services\VirtualGameIdService;
use Illuminate\Http\Request;

class SubmitRichPresenceAction extends BaseAuthenticatedApiAction
{
    protected int $gameId;
    protected string $richPresence;

    public function execute(int $gameId, string $richPresence, User $user): array
    {
        $this->gameId = $gameId;
        $this->richPresence = $richPresence;
        $this->user = $user;

        return $this->process();
    }

    protected function initialize(Request $request): ?array
    {
        if (!$request->has(['g', 'd'])) {
            return $this->missingParameters();
        }

        $this->gameId = request()->integer('g', 0);
        // The rich presence script should be sent as POST data due to potential size.
        $this->richPresence = request()->post('d', '');

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

        // Check if user has permission to update this game.
        if (!$this->user->can('update', $game)) {
            return $this->accessDenied();
        }

        // modifyGameRichPresence handles versioning automatically.
        $success = modifyGameRichPresence($this->user, $this->gameId, $this->richPresence);

        if (!$success) {
            return [
                'Success' => false,
                'Error' => 'Failed to update rich presence.',
            ];
        }

        return [
            'Success' => true,
        ];
    }
}
