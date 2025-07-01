<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\AchievementSetClaim;
use App\Platform\Data\GameData;
use Carbon\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('AchievementSetClaimGroup')]
class AchievementSetClaimGroupData extends Data
{
    public function __construct(
        public int $id,

        /**
         * Conceptually, a "claim" often has multiple users from a
         * player's point of view, eg: a collaboration.
         *
         * @var UserData[]
         */
        public array $users,

        public GameData $game,
        public int $claimType,
        public int $setType,
        public int $status,
        public Carbon $created,
        public Carbon $finished,
    ) {
    }

    public static function fromAchievementSetClaim(AchievementSetClaim $claim, array $users): self
    {
        $userData = array_map(fn ($user) => UserData::fromUser($user), $users);
        $gameData = GameData::from($claim->game)->include('badgeUrl', 'system');

        return new self(
            id: $claim->ID,
            users: $userData,
            game: $gameData,
            claimType: $claim->ClaimType,
            setType: $claim->SetType,
            status: $claim->Status,
            created: Carbon::parse($claim->Created),
            finished: Carbon::parse($claim->Finished),
        );
    }
}
