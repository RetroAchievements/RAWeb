<?php

declare(strict_types=1);

namespace App\Http\Actions;

use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Data\AchievementSetClaimGroupData;
use App\Models\AchievementSetClaim;
use Illuminate\Database\Eloquent\Builder;
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
        $claims = $this->fetchClaims($status, $count);

        return $this->transformClaimsForDisplay($claims);
    }

    /**
     * @return Builder<AchievementSetClaim>
     */
    private function buildBaseQuery(ClaimStatus $status): Builder
    {
        $query = AchievementSetClaim::query()
            ->whereIn('claim_type', [ClaimType::Primary, ClaimType::Collaboration])
            ->where('status', $status);

        // For completed claims, only show games from valid consoles with published achievements.
        if ($status === ClaimStatus::Complete) {
            $query->whereHas('game.system', fn ($q) => $q->whereIn('id', getValidConsoleIds()));
            $query->whereHas('game', fn ($q) => $q->whereHasPublishedAchievements());
        }

        return $query;
    }

    /**
     * @return EloquentCollection<int, AchievementSetClaim>
     */
    private function fetchClaims(ClaimStatus $status, int $count): EloquentCollection
    {
        $orderByField = $status === ClaimStatus::Complete ? 'finished_at' : 'created_at';
        $claimIds = [];
        $seenGameIds = [];

        foreach ($this->buildBaseQuery($status)
            ->select(['id', 'game_id'])
            ->orderByDesc($orderByField)
            ->orderByDesc('id')
            ->limit(50)
            ->cursor() as $claim) {
            $isNewGame = !isset($seenGameIds[$claim->game_id]);

            // Stop after we've collected every row belonging to the first N unique games.
            if ($isNewGame && count($seenGameIds) >= $count) {
                break;
            }

            $claimIds[] = $claim->id;
            $seenGameIds[$claim->game_id] = true;
        }

        if ($claimIds === []) {
            return new EloquentCollection();
        }

        return AchievementSetClaim::query()
            ->with(['game.system', 'user'])
            ->whereIn('id', $claimIds)
            ->orderByDesc($orderByField)
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Transform the claims into a display-friendly format.
     * We want to merge collaborative claims into a single row, grouped by game.
     *
     * @param EloquentCollection<int, AchievementSetClaim> $claims
     * @return Collection<int, AchievementSetClaimGroupData>
     */
    private function transformClaimsForDisplay(EloquentCollection $claims): Collection
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
            ->map(fn ($group) => AchievementSetClaimGroupData::fromAchievementSetClaim(
                    $group['claim'],
                    $group['users']
                )
            )
            ->values();
    }
}
