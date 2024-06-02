<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Community\Enums\ClaimType;
use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimSpecial;
use App\Community\Enums\ClaimStatus;
use App\Enums\Permissions;
use App\Models\AchievementSetClaim;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class AchievementSetClaimListService
{
    public int $totalClaims = 0;
    public int $numFilteredClaims = 0;
    public int $perPage = 50;
    public int $pageNumber = 0;
    public int $totalPages = 0;
    public string $sortOrder='-enddate';

    public function getFilterOptions(Request $request): array
    {
        if ($this->perPage !== 0) {
            $validatedData = $request->validate([
                'page.number' => 'sometimes|integer|min:1',
            ]);
            $this->pageNumber = (int) ($validatedData['page']['number'] ?? 1);
        }

        $validatedData = $request->validate([
            'sort' => 'sometimes|string|in:console,title,developer,-claimdate,-enddate,claimdate,enddate',
            'filter.type' => 'sometimes|integer|min:-1|max:2',
            'filter.setType' => 'sometimes|integer|min:-1|max:2',
            'filter.status' => 'sometimes|string|in:all,active,review,complete,dropped,activeOrReview',
            'filter.special' => 'sometimes|integer|min:-1|max:3',
            'filter.developerType' => 'sometimes|string|in:all,full,junior',
        ]);

        $this->sortOrder = $validatedData['sort'] ?? $this->sortOrder;

        return [
            'type' => (int) ($validatedData['filter']['type'] ?? 0),
            'setType' => (int) ($validatedData['filter']['setType'] ?? -1),
            'status' => $validatedData['filter']['status'] ?? 'active',
            'special' => (int) ($validatedData['filter']['special'] ?? -1),
            'developerType' => $validatedData['filter']['developerType'] ?? 'all',
        ];
    }

    public function getSelectFilters(bool $showActiveStatuses = false): array
    {
        $availableSelectFilters = [];

        $availableSelectFilters[] = [
            'kind' => 'type',
            'label' => 'Claim Type',
            'options' => [
                -1 => 'All Claims',
                ClaimType::Primary => ClaimType::toString(ClaimType::Primary),
                ClaimType::Collaboration => ClaimType::toString(ClaimType::Collaboration),
            ],
        ];

        $availableSelectFilters[] = [
            'kind' => 'setType',
            'label' => 'Set Type',
            'options' => [
                -1 => 'All',
                ClaimSetType::NewSet => ClaimSetType::toString(ClaimSetType::NewSet),
                ClaimSetType::Revision => ClaimSetType::toString(ClaimSetType::Revision),
            ],
        ];

        if ($showActiveStatuses) {
            $availableSelectFilters[] = [
                'kind' => 'status',
                'label' => 'Status',
                'options' => [
                    'activeOrReview' => 'All',
                    'active' => ClaimStatus::toString(ClaimStatus::Active),
                    'review' => ClaimStatus::toString(ClaimStatus::InReview),
                ],
            ];
        } else {
            $availableSelectFilters[] = [
                'kind' => 'status',
                'label' => 'Status',
                'options' => [
                    'all' => 'All',
                    'active' => ClaimStatus::toString(ClaimStatus::Active),
                    'review' => ClaimStatus::toString(ClaimStatus::InReview),
                    'complete' => ClaimStatus::toString(ClaimStatus::Complete),
                    'dropped' => ClaimStatus::toString(ClaimStatus::Dropped),
                ],
            ];
        }

        $availableSelectFilters[] = [
            'kind' => 'special',
            'label' => 'Special',
            'options' => [
                -1 => 'All',
                ClaimSpecial::None => ClaimSpecial::toString(ClaimSpecial::None),
                ClaimSpecial::OwnRevision => ClaimSpecial::toString(ClaimSpecial::OwnRevision),
                ClaimSpecial::FreeRollout => ClaimSpecial::toString(ClaimSpecial::FreeRollout),
                ClaimSpecial::ScheduledRelease => ClaimSpecial::toString(ClaimSpecial::ScheduledRelease),
            ],
        ];

        $availableSelectFilters[] = [
            'kind' => 'developerType',
            'label' => 'Developer Type',
            'options' => [
                'all' => 'All',
                'full' => 'Developer',
                'junior' => 'Junior Developer',
            ],
        ];

        return $availableSelectFilters;
    }

    public function getSorts(): array
    {
        return [
            'title' => 'Game Title',
            'developer' => 'Developer',
            '-claimdate' => 'Latest Claim Date',
            'claimdate' => 'Oldest Claim Date',
            '-enddate' => 'Latest Completion Date',
            'enddate' => 'Oldest Completion Date',
        ];
    }

    /**
     * @param Builder<AchievementSetClaim> $claims
     *
     * @return Collection<int, AchievementSetClaim>
     */
    public function getClaims(array $filterOptions, ?Builder $claims = null): Collection
    {
        return $this->buildQuery($filterOptions, $claims)->get();
    }

    /**
     * @param Builder<AchievementSetClaim> $claims
     *
     * @return Builder<AchievementSetClaim>
     */
    public function buildQuery(array $filterOptions, ?Builder $claims = null): Builder
    {
        if ($claims === null) {
            $claims = AchievementSetClaim::query();
        }

        $this->totalClaims = $claims->count();

        switch ($filterOptions['status']) {
            case 'active':
                $claims->active();
                break;

            case 'activeOrReview':
                $claims->activeOrReview();
                break;

            case 'review':
                $claims->inReview();
                break;

            case 'complete':
                $claims->complete();
                break;

            case 'review':
                $claims->dropped();
                break;
        }

        if ($filterOptions['type'] !== -1) {
            $claims->where('ClaimType', $filterOptions['type']);
        }

        if ($filterOptions['setType'] !== -1) {
            $claims->where('SetType', $filterOptions['setType']);
        }

        if ($filterOptions['special'] !== -1) {
            $claims->where('Special', $filterOptions['special']);
        }

        switch ($filterOptions['developerType']) {
            case 'full':
                $claims->whereHas('user', function($query) {
                    $query->where('Permissions', '>=', Permissions::Developer);
                });
                break;

            case 'junior':
                $claims->whereHas('user', function($query) {
                    $query->where('Permissions', '=', Permissions::JuniorDeveloper);
                });
                break;
        }

        $this->numFilteredClaims = $claims->count();

        if ($this->perPage > 0) {
            $this->totalPages = (int) ceil($this->numFilteredClaims / $this->perPage);

            if ($this->pageNumber < 1 || $this->pageNumber > $this->totalPages) {
                $this->pageNumber = 1;
            }

            $claims->offset(($this->pageNumber - 1) * $this->perPage)->take($this->perPage);
        }

        switch ($this->sortOrder) {
            case 'title':
                $claims->join('GameData', 'GameData.ID', '=', 'SetClaim.game_id')
                       ->orderByRaw(ifStatement("GameData.Title LIKE '~%'", 1, 0))
                       ->orderBy('GameData.Title')
                       ->orderByDesc('SetClaim.Finished')
                       ->select('SetClaim.*');
                break;

            case 'developer':
                $claims->join('UserAccounts', 'UserAccounts.ID', '=', 'SetClaim.user_id')
                       ->orderBy('UserAccounts.User')
                       ->orderByDesc('SetClaim.Finished')
                       ->select('SetClaim.*');
                break;

            case '-claimdate':
                $claims->orderByDesc('Created');
                break;

            case 'claimdate':
                $claims->orderBy('Created');
                break;

            default:
            case '-enddate':
                $claims->orderByDesc('Finished');
                break;

            case 'enddate':
                $claims->orderBy('Finished');
                break;
        }

        return $claims->with(['game.system', 'user']);
    }
}
