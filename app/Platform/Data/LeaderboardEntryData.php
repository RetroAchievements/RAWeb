<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Models\LeaderboardEntry;
use App\Platform\Enums\ValueFormat;
use Carbon\Carbon;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('LeaderboardEntry')]
class LeaderboardEntryData extends Data
{
    public function __construct(
        public int $id,
        public Lazy|int $score,
        public Lazy|string $formattedScore,
        public Lazy|Carbon $createdAt,
    ) {
    }

    public static function fromLeaderboardEntry(LeaderboardEntry $leaderboardEntry): self
    {
        return new self(
            id: $leaderboardEntry->id,
            score: Lazy::create(fn () => $leaderboardEntry->score),
            formattedScore: Lazy::create(fn () => ValueFormat::format($leaderboardEntry->score, $leaderboardEntry->leaderboard->format)),
            createdAt: Lazy::create(fn () => $leaderboardEntry->created_at),
        );
    }
}
