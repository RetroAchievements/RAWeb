<?php

declare(strict_types=1);

namespace App\Http\Actions;

use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Data\AchievementSetClaimGroupData;
use App\Models\AchievementSetClaim;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class BuildHomePageClaimsDataAction
{
    /**
     * Get the most recent achievement claims for the home page, grouped by game.
     *
     * @return Collection<int, AchievementSetClaimGroupData>
     */
    public function execute(ClaimStatus $status, int $count): Collection
    {
        $claims = $this->fetchClaims($status);

        return $this->transformClaimsForDisplay($claims, $count);
    }

    /**
     * @return EloquentCollection<int, AchievementSetClaim>
     */
    private function fetchClaims(ClaimStatus $status): EloquentCollection
    {
        $query = AchievementSetClaim::query()
            ->with(['game.system', 'user'])
            ->whereIn('claim_type', [ClaimType::Primary, ClaimType::Collaboration])
            ->where('status', $status);

        // For completed claims, only show games from valid consoles with published achievements.
        if ($status === ClaimStatus::Complete) {
            $query->whereHas('game.system', fn ($q) => $q->whereIn('ID', getValidConsoleIds()));
            $query->whereHas('game', fn ($q) => $q->whereHasPublishedAchievements());
        }

        $orderByField = $status === ClaimStatus::Complete ? 'finished_at' : 'created_at';

        return $query->orderByDesc($orderByField)
            ->limit(20)
            ->get();
    }

    /**
     * Transform the claims into a display-friendly format.
     * We want to merge collaborative claims into a single row, grouped by game.
     *
     * @param EloquentCollection<int, AchievementSetClaim> $claims
     * @return Collection<int, AchievementSetClaimGroupData>
     */
    private function transformClaimsForDisplay(EloquentCollection $claims, int $count): Collection
    {
        return $claims
            ->groupBy('game.id')
            ->map(function (EloquentCollection $gameClaims) {
                $uniqueUserClaims = $gameClaims->groupBy('user.id')
                    ->map->first();

                return [
                    'claim' => $uniqueUserClaims->sortBy('created_at')->first(),
                    'users' => $uniqueUserClaims->pluck('user')->all(),
                ];
            })
            ->take($count)
            ->map(fn ($group) => AchievementSetClaimGroupData::fromAchievementSetClaim(
                    $group['claim'],
                    $group['users']
                )
            )
            ->values();
    }
}
