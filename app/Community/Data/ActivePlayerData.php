<?php

declare(strict_types=1);

namespace App\Community\Data;

use App\Data\UserData;
use App\Models\Game;
use App\Models\User;
use App\Platform\Data\GameData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('ActivePlayer')]
class ActivePlayerData extends Data
{
    public function __construct(
        public UserData $user,
        public GameData $game,
    ) {
    }

    public static function fromHydratedTuple(User $user, Game $game): self
    {
        return new self(
            user: UserData::fromUser($user)->include('richPresenceMsg'),
            game: GameData::fromGame($game),
        );
    }
}
