<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimSpecial;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Enums\Permissions;
use App\Models\AchievementSetClaim;
use App\Models\System;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AchievementSetClaimListService
{
    public int $numFilteredClaims = 0;
    public int $perPage = 50;
    public int $pageNumber = 0;
    public int $totalPages = 0;

    public string $sortOrder = '-enddate';
    public array $defaultFilters = [
        'type' => ClaimType::Primary,
        'setType' => -1,
        'status' => 'active',
        'special' => -1,
        'developerType' => 'all',
        'system' => 0,
    ];

    public function getFilterOptions(Request $request): array
    {
        if ($this->perPage !== 0) {
            $validatedData = $request->validate([
                'page.number' => 'sometimes|integer|min:1',
            ]);
            $this->pageNumber = (int) ($validatedData['page']['number'] ?? 1);
        }

        $validatedData = $request->validate([
            'sort' => 'sometimes|string|in:console,title,developer,-claimdate,-enddate,-expiring,claimdate,enddate,expiring',
            'filter.type' => 'sometimes|integer|min:-1|max:2',
            'filter.setType' => 'sometimes|integer|min:-1|max:2',
            'filter.status' => 'sometimes|string|in:all,active,review,complete,dropped,activeOrInReview',
            'filter.special' => 'sometimes|integer|min:-1|max:3',
            'filter.developerType' => 'sometimes|string|in:all,full,junior',
            'filter.system' => 'sometimes|integer|min:0',
        ]);

        $this->sortOrder = $validatedData['sort'] ?? $this->sortOrder;

        return [
            'type' => (int) ($validatedData['filter']['type'] ?? $this->defaultFilters['type']),
            'setType' => (int) ($validatedData['filter']['setType'] ?? $this->defaultFilters['setType']),
            'status' => $validatedData['filter']['status'] ?? $this->defaultFilters['status'],
            'special' => (int) ($validatedData['filter']['special'] ?? $this->defaultFilters['special']),
            'developerType' => $validatedData['filter']['developerType'] ?? $this->defaultFilters['developerType'],
            'system' => (int) ($validatedData['filter']['system'] ?? $this->defaultFilters['system']),
        ];
    }

    public function getClaimTypeFilter(): array
    {
        return [
            'kind' => 'type',
            'label' => 'Claim Type',
            'options' => [
                -1 => 'All Claims',
                ClaimType::Primary => ClaimType::toString(ClaimType::Primary),
                ClaimType::Collaboration => ClaimType::toString(ClaimType::Collaboration),
            ],
        ];
    }

    public function getSetTypeFilter(): array
    {
        return [
            'kind' => 'setType',
            'label' => 'Set Type',
            'options' => [
                -1 => 'All',
                ClaimSetType::NewSet => ClaimSetType::toString(ClaimSetType::NewSet),
                ClaimSetType::Revision => ClaimSetType::toString(ClaimSetType::Revision),
            ],
        ];
    }

    public function getActiveStatusesFilter(): array
    {
        return [
            'kind' => 'status',
            'label' => 'Status',
            'options' => [
                'activeOrInReview' => 'All',
                'active' => ClaimStatus::toString(ClaimStatus::Active),
                'review' => ClaimStatus::toString(ClaimStatus::InReview),
            ],
        ];
    }

    public function getMergedActiveStatusesFilter(): array
    {
        return [
            'kind' => 'status',
            'label' => 'Status',
            'options' => [
                'all' => 'All',
                'activeOrInReview' => 'In Progress',
                'complete' => ClaimStatus::toString(ClaimStatus::Complete),
                'dropped' => ClaimStatus::toString(ClaimStatus::Dropped),
            ],
        ];
    }

    public function getStatusFilter(): array
    {
        return [
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

    public function getSpecialFilter(): array
    {
        return [
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
    }

    public function getDeveloperTypeFilter(): array
    {
        return [
            'kind' => 'developerType',
            'label' => 'Developer Type',
            'options' => [
                'all' => 'All',
                'full' => 'Developer',
                'junior' => 'Junior Developer',
            ],
        ];
    }

    public function getSystemFilter(bool $onlyValid = true): array
    {
        $systems = [
            0 => 'All systems',
        ];

        foreach (System::orderBy('name')->get() as $system) {
            if (System::isGameSystem($system->id)) {
                if (isValidConsoleId($system->id)) {
                    $systems[$system->id] = $system->name;
                } elseif (!$onlyValid) {
                    $systemClaims = AchievementSetClaim::whereHas('game', function ($query) use ($system) {
                        $query->where('ConsoleID', '=', $system->id);
                    });
                    if ($systemClaims->exists()) {
                        $systems[$system->id] = $system->name;
                    }
                }
            }
        }

        return [
            'kind' => 'system',
            'label' => 'System',
            'options' => $systems,
        ];
    }

    public function getSorts(bool $withDeveloper = true, bool $withExpiring = true, bool $withGame = true): array
    {
        if ($withGame) {
            $sorts['title'] = 'Game Title';
        }

        if ($withDeveloper) {
            $sorts['developer'] = 'Developer';
        }

        $sorts['-claimdate'] = 'Newest Claim';
        $sorts['claimdate'] = 'Oldest Claim';

        if ($withExpiring) {
            $sorts['-expiring'] = 'Expiring Soonest';
            $sorts['expiring'] = 'Expiring Latest';
        }

        return $sorts;
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
    private function buildQuery(array $filterOptions, ?Builder $claims): Builder
    {
        if ($claims === null) {
            $claims = AchievementSetClaim::query();
        }

        switch ($filterOptions['status']) {
            case 'active':
                $claims->active();
                break;

            case 'activeOrInReview':
                $claims->activeOrInReview();
                break;

            case 'review':
                $claims->inReview();
                break;

            case 'complete':
                $claims->complete();
                break;

            case 'dropped':
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
                $claims->whereHas('user', function ($query) {
                    $query->where('Permissions', '>=', Permissions::Developer);
                });
                break;

            case 'junior':
                $claims->whereHas('user', function ($query) {
                    $query->where('Permissions', '=', Permissions::JuniorDeveloper);
                });
                break;
        }

        if ($filterOptions['system'] !== 0) {
            $claims->whereHas('game', function ($query) use ($filterOptions) {
                $query->where('ConsoleID', '=', $filterOptions['system']);
            });
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

            case '-expiring':
                $claims->orderByRaw(ifStatement("SetClaim.Status IN(" . ClaimStatus::Active . ',' . ClaimStatus::InReview . ")", 0, 1))
                       ->orderBy('Finished');
                break;

            case 'expiring':
                $claims->orderByRaw(ifStatement("SetClaim.Status IN(" . ClaimStatus::Active . ',' . ClaimStatus::InReview . ")", 0, 1))
                       ->orderByDesc('Finished');
                break;
        }

        return $claims->with(['game.system', 'user']);
    }

    public function getGameColumn(): array
    {
        return [
            'header' => 'Game',
            'type' => 'game',
            'value' => fn ($claim) => $claim->game,
        ];
    }

    public function getDeveloperColumn(): array
    {
        return [
            'header' => 'Developer',
            'type' => 'user',
            'value' => fn ($claim) => $claim->user,
        ];
    }

    public function getClaimTypeColumn(): array
    {
        return [
            'header' => 'Claim Type',
            'type' => 'text',
            'value' => fn ($claim) => ClaimType::toString($claim->ClaimType),
        ];
    }

    public function getSetTypeColumn(): array
    {
        return [
            'header' => 'Set Type',
            'type' => 'text',
            'value' => fn ($claim) => ClaimSetType::toString($claim->SetType),
        ];
    }

    public function getStatusColumn(): array
    {
        return [
            'header' => 'Status',
            'type' => 'text',
            'value' => fn ($claim) => ClaimStatus::toString($claim->Status),
        ];
    }

    public function getSpecialColumn(): array
    {
        return [
            'header' => 'Special',
            'type' => 'text',
            'value' => fn ($claim) => ClaimSpecial::toString($claim->Special),
        ];
    }

    public function getClaimDateColumn(): array
    {
        return [
            'header' => 'Claimed At',
            'type' => 'date',
            'value' => fn ($claim) => $claim->Created ? getNiceDate($claim->Created->unix()) : 'Unknown',
        ];
    }

    public function getEndDateColumn(): array
    {
        return [
            'header' => 'Expires/Finished At',
            'type' => 'date',
            'value' => fn ($claim) => $claim->Finished ? getNiceDate($claim->Finished->unix()) : 'Unknown',
        ];
    }

    public function getFinishedDateColumn(): array
    {
        return [
            'header' => 'Finished At',
            'type' => 'date',
            'value' => fn ($claim) => $claim->Finished ? getNiceDate($claim->Finished->unix()) : 'Unknown',
        ];
    }

    public function getExpirationDateColumn(): array
    {
        return [
            'header' => 'Expires At',
            'type' => 'date',
            'value' => fn ($claim) => $claim->Finished ? getNiceDate($claim->Finished->unix()) : 'Unknown',
        ];
    }

    public function getExpirationStatusColumn(): array
    {
        return [
            'header' => 'Expiration Status',
            'type' => 'expiration',
            'value' => function ($claim) {
                if (!ClaimStatus::isActive($claim->Status)) {
                    return [
                        'isExpired' => false,
                        'value' => '',
                    ];
                } else {
                    $now = Carbon::now();

                    return [
                        'isExpired' => ($claim->Finished < $now),
                        'value' => $claim->Finished->diffForHumans($now, ['syntax' => Carbon::DIFF_RELATIVE_TO_NOW]),
                    ];
                }
            },
        ];
    }
}
