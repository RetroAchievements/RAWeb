<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
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
        public Lazy|int $extensionsCount,
        public Lazy|int $minutesActive,
        public Lazy|int $minutesLeft,
        public Lazy|bool $isCompletable,
        public Lazy|bool $isDroppable,
        public Lazy|bool $isExtendable,
    ) {
    }

    public static function fromAchievementSetClaim(
        AchievementSetClaim $claim,
        bool $isValidConsole = false,
        bool $hasOfficialAchievements = false
    ): self {
        $now = Carbon::now();
        $minutesLeft = (int) $now->diffInMinutes($claim->Finished, false);
        $minutesActive = (int) $claim->Created->diffInMinutes($now);

        // Only extendable if it's a primary claim AND it expires within 7 days (10080 minutes).
        $isExtendable = $claim->ClaimType === ClaimType::Primary && $minutesLeft <= 10080;

        // Only droppable if it's not in review status.
        $isDroppable = $claim->Status !== ClaimStatus::InReview;

        // Only completable if it's a primary claim, not in review status, the system is rolled out, and the set has published achievements.
        $isCompletable = $claim->ClaimType === ClaimType::Primary
            && $claim->Status !== ClaimStatus::InReview
            && $isValidConsole
            && $hasOfficialAchievements;

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
            extensionsCount: Lazy::create(fn () => $claim->Extension ?? 0),
            isCompletable: Lazy::create(fn () => $isCompletable),
            isDroppable: Lazy::create(fn () => $isDroppable),
            isExtendable: Lazy::create(fn () => $isExtendable),
            minutesActive: Lazy::create(fn () => $minutesActive),
            minutesLeft: Lazy::create(fn () => $minutesLeft),
        );
    }
}
