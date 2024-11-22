<?php

declare(strict_types=1);

namespace App\Http\Actions;

use App\Community\Enums\AwardType;
use App\Data\StaticGameAwardData;
use App\Data\UserData;
use App\Models\Game;
use App\Models\StaticData;
use App\Models\User;
use App\Platform\Data\GameData;
use Carbon\Carbon;
use InvalidArgumentException;

class BuildMostRecentGameAwardDataAction
{
    public function execute(?StaticData $staticData, int $type = AwardType::Mastery): ?StaticGameAwardData
    {
        if (!$staticData) {
            return null;
        }

        if (!AwardType::isGame($type)) {
            throw new InvalidArgumentException('Invalid game award type');
        }

        $suffix = $type === AwardType::Mastery ? 'mastered' : 'beaten';

        $game = Game::find($staticData->{"last_game_hardcore_{$suffix}_game_id"});
        $user = User::find($staticData->{"last_game_hardcore_{$suffix}_user_id"});

        if (!$game || !$user) {
            return null;
        }

        return new StaticGameAwardData(
            game: GameData::fromGame($game)->include('badgeUrl', 'system.nameShort', 'system.iconUrl'),
            user: UserData::fromUser($user),
            awardedAt: Carbon::parse($staticData->{"last_game_hardcore_{$suffix}_at"}),
        );
    }
}
