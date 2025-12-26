<?php

declare(strict_types=1);

namespace App\Data;

use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
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
        public ClaimType $claimType,
        public ClaimSetType $setType,
        public ClaimStatus $status,
        public Carbon $created,
        public Carbon $finished,
    ) {
    }

    public static function fromAchievementSetClaim(AchievementSetClaim $claim, array $users): self
    {
        $userData = array_map(fn ($user) => UserData::fromUser($user), $users);
        $gameData = GameData::from($claim->game)->include('badgeUrl', 'system');

        return new self(
            id: $claim->id,
            users: $userData,
            game: $gameData,
            claimType: $claim->claim_type,
            setType: $claim->set_type,
            status: $claim->status,
            created: $claim->created_at,
            finished: $claim->finished_at,
        );
    }
}
