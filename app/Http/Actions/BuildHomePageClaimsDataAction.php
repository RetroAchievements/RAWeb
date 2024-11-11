<?php

declare(strict_types=1);

namespace App\Http\Actions;

use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Data\AchievementSetClaimData;
use App\Models\AchievementSetClaim;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class BuildHomePageClaimsDataAction
{
    /**
     * Get the most recent achievement claims for the home page, grouped by game.
     * $status corresponds to `ClaimStatus`.
     *
     * @return Collection<int, AchievementSetClaimData>
     */
    public function execute(int $status, int $count): Collection
    {
        $claims = $this->fetchClaims($status);

        return $this->transformClaimsForDisplay($claims, $count);
    }

    /**
     * @return EloquentCollection<int, AchievementSetClaim>
     */
    private function fetchClaims(int $status): EloquentCollection
    {
        $query = AchievementSetClaim::query()
            ->with(['game.system', 'user'])
            ->whereIn('ClaimType', [ClaimType::Primary, ClaimType::Collaboration])
            ->where('Status', $status);

        // For completed claims, only show games from valid consoles.
        if ($status === ClaimStatus::Complete) {
            $query->whereHas('game.system', fn ($q) => $q->whereIn('ID', getValidConsoleIds()));
        }

        $orderByField = $status === ClaimStatus::Complete ? 'Finished' : 'Created';

        return $query->orderByDesc($orderByField)
            ->limit(20)
            ->get();
    }

    /**
     * Transform the claims into a display-friendly format.
     * We want to merge collaborative claims into a single row, grouped by game.
     *
     * @param EloquentCollection<int, AchievementSetClaim> $claims
     * @return Collection<int, AchievementSetClaimData>
     */
    private function transformClaimsForDisplay(EloquentCollection $claims, int $count): Collection
    {
        return $claims
            ->groupBy('game.id')
            ->map(function (EloquentCollection $gameClaims) {
                $uniqueUserClaims = $gameClaims->groupBy('user.id')
                    ->map->first();

                return [
                    'claim' => $uniqueUserClaims->sortBy('Created')->first(),
                    'users' => $uniqueUserClaims->pluck('user')->all(),
                ];
            })
            ->take($count)
            ->map(fn ($group) => AchievementSetClaimData::fromAchievementSetClaim(
                    $group['claim'],
                    $group['users']
                )
            )
            ->values();
    }
}
