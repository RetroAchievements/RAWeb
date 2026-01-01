<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Community\Enums\ClaimSetType;
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
        public Lazy|ClaimType $claimType,
        public Lazy|ClaimSetType $setType,
        public Lazy|ClaimStatus $status,
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
        bool $hasOfficialAchievements = false,
    ): self {
        $now = Carbon::now();
        $minutesLeft = (int) $now->diffInMinutes($claim->finished_at, false);
        $minutesActive = (int) $claim->created_at->diffInMinutes($now);

        // Only extendable if it's a primary claim AND it expires within 7 days (10080 minutes).
        $isExtendable = $claim->claim_type === ClaimType::Primary && $minutesLeft <= 10080;

        // Only droppable if it's not in review status.
        $isDroppable = $claim->status !== ClaimStatus::InReview;

        // Only completable if it's a primary claim, not in review status, the system is rolled out, and the set has published achievements.
        $isCompletable = $claim->claim_type === ClaimType::Primary
            && $claim->status !== ClaimStatus::InReview
            && $isValidConsole
            && $hasOfficialAchievements;

        return new self(
            id: $claim->id,
            user: Lazy::create(fn () => UserData::fromUser($claim->user)),
            game: Lazy::create(fn () => GameData::from($claim->game)->include('badgeUrl', 'system')),
            claimType: Lazy::create(fn () => $claim->claim_type),
            setType: Lazy::create(fn () => $claim->set_type),
            status: Lazy::create(fn () => $claim->status),
            createdAt: Lazy::create(fn () => $claim->created_at),
            finishedAt: Lazy::create(fn () => $claim->finished_at),
            userLastPlayedAt: Lazy::create(fn () => $claim->user_last_played_at),
            extensionsCount: Lazy::create(fn () => $claim->extensions_count ?? 0),
            isCompletable: Lazy::create(fn () => $isCompletable),
            isDroppable: Lazy::create(fn () => $isDroppable),
            isExtendable: Lazy::create(fn () => $isExtendable),
            minutesActive: Lazy::create(fn () => $minutesActive),
            minutesLeft: Lazy::create(fn () => $minutesLeft),
        );
    }
}
