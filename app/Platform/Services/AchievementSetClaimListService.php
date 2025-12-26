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
        'type' => 'all',
        'setType' => 'all',
        'status' => 'active',
        'special' => 'all',
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

        $claimTypeValues = implode(',', array_map(fn ($case) => $case->value, ClaimType::cases()));
        $setTypeValues = implode(',', array_map(fn ($case) => $case->value, ClaimSetType::cases()));
        $specialValues = implode(',', array_map(fn ($case) => $case->value, ClaimSpecial::cases()));

        $validatedData = $request->validate([
            'sort' => 'sometimes|string|in:console,title,developer,-claimdate,-enddate,-expiring,claimdate,enddate,expiring',
            'filter.type' => "sometimes|string|in:all,{$claimTypeValues}",
            'filter.setType' => "sometimes|string|in:all,{$setTypeValues}",
            'filter.status' => 'sometimes|string|in:all,active,review,complete,dropped,activeOrInReview',
            'filter.special' => "sometimes|string|in:all,{$specialValues}",
            'filter.developerType' => 'sometimes|string|in:all,full,junior',
            'filter.system' => 'sometimes|integer|min:0',
        ]);

        $this->sortOrder = $validatedData['sort'] ?? $this->sortOrder;

        return [
            'type' => $validatedData['filter']['type'] ?? $this->defaultFilters['type'],
            'setType' => $validatedData['filter']['setType'] ?? $this->defaultFilters['setType'],
            'status' => $validatedData['filter']['status'] ?? $this->defaultFilters['status'],
            'special' => $validatedData['filter']['special'] ?? $this->defaultFilters['special'],
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
                'all' => 'All Claims',
                ClaimType::Primary->value => ClaimType::Primary->label(),
                ClaimType::Collaboration->value => ClaimType::Collaboration->label(),
            ],
        ];
    }

    public function getSetTypeFilter(): array
    {
        return [
            'kind' => 'setType',
            'label' => 'Set Type',
            'options' => [
                'all' => 'All',
                ClaimSetType::NewSet->value => ClaimSetType::NewSet->label(),
                ClaimSetType::Revision->value => ClaimSetType::Revision->label(),
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
                'active' => ClaimStatus::Active->label(),
                'review' => ClaimStatus::InReview->label(),
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
                'complete' => ClaimStatus::Complete->label(),
                'dropped' => ClaimStatus::Dropped->label(),
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
                'active' => ClaimStatus::Active->label(),
                'review' => ClaimStatus::InReview->label(),
                'complete' => ClaimStatus::Complete->label(),
                'dropped' => ClaimStatus::Dropped->label(),
            ],
        ];
    }

    public function getSpecialFilter(): array
    {
        return [
            'kind' => 'special',
            'label' => 'Special',
            'options' => [
                'all' => 'All',
                ClaimSpecial::None->value => ClaimSpecial::None->label(),
                ClaimSpecial::OwnRevision->value => ClaimSpecial::OwnRevision->label(),
                ClaimSpecial::FreeRollout->value => ClaimSpecial::FreeRollout->label(),
                ClaimSpecial::ScheduledRelease->value => ClaimSpecial::ScheduledRelease->label(),
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

        foreach (System::gameSystems()->orderBy('name')->get() as $system) {
            if ($system->active) {
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

        if ($filterOptions['type'] !== 'all') {
            $claims->where('claim_type', $filterOptions['type']);
        }

        if ($filterOptions['setType'] !== 'all') {
            $claims->where('set_type', $filterOptions['setType']);
        }

        if ($filterOptions['special'] !== 'all') {
            $claims->where('special_type', $filterOptions['special']);
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
                $claims->join('GameData', 'GameData.ID', '=', 'achievement_set_claims.game_id')
                    ->orderByRaw(ifStatement("GameData.Title LIKE '~%'", 1, 0))
                    ->orderBy('GameData.Title')
                    ->orderByDesc('achievement_set_claims.finished_at')
                    ->select('achievement_set_claims.*');
                break;

            case 'developer':
                $claims->join('UserAccounts', 'UserAccounts.ID', '=', 'achievement_set_claims.user_id')
                    ->orderBy('UserAccounts.User')
                    ->orderByDesc('achievement_set_claims.finished_at')
                    ->select('achievement_set_claims.*');
                break;

            case '-claimdate':
                $claims->orderByDesc('created_at');
                break;

            case 'claimdate':
                $claims->orderBy('created_at');
                break;

            default:
            case '-enddate':
                $claims->orderByDesc('finished_at');
                break;

            case 'enddate':
                $claims->orderBy('finished_at');
                break;

            case '-expiring':
                $claims->orderByRaw(ifStatement("achievement_set_claims.status IN('" . ClaimStatus::Active->value . "','" . ClaimStatus::InReview->value . "')", 0, 1))
                    ->orderBy('finished_at');
                break;

            case 'expiring':
                $claims->orderByRaw(ifStatement("achievement_set_claims.status IN('" . ClaimStatus::Active->value . "','" . ClaimStatus::InReview->value . "')", 0, 1))
                    ->orderByDesc('finished_at');
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
            'value' => fn ($claim) => $claim->claim_type->label(),
        ];
    }

    public function getSetTypeColumn(): array
    {
        return [
            'header' => 'Set Type',
            'type' => 'text',
            'value' => fn ($claim) => $claim->set_type->label(),
        ];
    }

    public function getStatusColumn(): array
    {
        return [
            'header' => 'Status',
            'type' => 'text',
            'value' => fn ($claim) => $claim->status->label(),
        ];
    }

    public function getSpecialColumn(): array
    {
        return [
            'header' => 'Special',
            'type' => 'text',
            'value' => fn ($claim) => $claim->special_type->label(),
        ];
    }

    public function getClaimDateColumn(): array
    {
        return [
            'header' => 'Claimed At',
            'type' => 'date',
            'value' => fn ($claim) => $claim->created_at ? getNiceDate($claim->created_at->unix()) : 'Unknown',
        ];
    }

    public function getEndDateColumn(): array
    {
        return [
            'header' => 'Expires/Finished At',
            'type' => 'date',
            'value' => fn ($claim) => $claim->finished_at ? getNiceDate($claim->finished_at->unix()) : 'Unknown',
        ];
    }

    public function getFinishedDateColumn(): array
    {
        return [
            'header' => 'Finished At',
            'type' => 'date',
            'value' => fn ($claim) => $claim->finished_at ? getNiceDate($claim->finished_at->unix()) : 'Unknown',
        ];
    }

    public function getExpirationDateColumn(): array
    {
        return [
            'header' => 'Expires At',
            'type' => 'date',
            'value' => fn ($claim) => $claim->finished_at ? getNiceDate($claim->finished_at->unix()) : 'Unknown',
        ];
    }

    public function getExpirationStatusColumn(): array
    {
        return [
            'header' => 'Expiration Status',
            'type' => 'expiration',
            'value' => function ($claim) {
                if (!$claim->status->isActive()) {
                    return [
                        'isExpired' => false,
                        'value' => '',
                    ];
                } else {
                    $now = Carbon::now();

                    return [
                        'isExpired' => ($claim->finished_at < $now),
                        'value' => $claim->finished_at->diffForHumans($now, ['syntax' => Carbon::DIFF_RELATIVE_TO_NOW]),
                    ];
                }
            },
        ];
    }
}
