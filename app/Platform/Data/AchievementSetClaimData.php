<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Data\UserData;
use App\Models\AchievementSetClaim;
use Carbon\Carbon;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('AchievementSetClaim')]
class AchievementSetClaimData extends Data
{
    public function __construct(
        public int $id,
        public Lazy|UserData $user,
        public Lazy|GameData $game,
        public Lazy|int $claimType,
        public Lazy|int $setType,
        public Lazy|int $status,
        public Lazy|Carbon $createdAt,
        public Lazy|Carbon $finishedAt,
        public Lazy|Carbon|null $userLastPlayedAt,
    ) {
    }

    public static function fromAchievementSetClaim(AchievementSetClaim $claim): self
    {
        return new self(
            id: $claim->ID,
            user: Lazy::create(fn () => UserData::fromUser($claim->user)),
            game: Lazy::create(fn () => GameData::from($claim->game)->include('badgeUrl', 'system')),
            claimType: Lazy::create(fn () => $claim->ClaimType),
            setType: Lazy::create(fn () => $claim->SetType),
            status: Lazy::create(fn () => $claim->Status),
            createdAt: Lazy::create(fn () => Carbon::parse($claim->Created)),
            finishedAt: Lazy::create(fn () => Carbon::parse($claim->Finished)),
            userLastPlayedAt: Lazy::create(fn () => $claim->user_last_played_at),
        );
    }
}
