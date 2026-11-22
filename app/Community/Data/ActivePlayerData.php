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

    public static function fromHydratedCachedValue(User $user, Game $game, ?string $richPresence): self
    {
        $userData = UserData::fromUser($user);
        $userData->richPresence = $richPresence;

        return new self(
            user: $userData,
            game: GameData::fromGame($game)->include('badgeUrl'),
        );
    }
}
