<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimSpecial;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Enums\Permissions;
use App\Models\AchievementSetClaim;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;

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
        ]);

        $this->sortOrder = $validatedData['sort'] ?? $this->sortOrder;

        return [
            'type' => (int) ($validatedData['filter']['type'] ?? $this->defaultFilters['type']),
            'setType' => (int) ($validatedData['filter']['setType'] ?? $this->defaultFilters['setType']),
            'status' => $validatedData['filter']['status'] ?? $this->defaultFilters['status'],
            'special' => (int) ($validatedData['filter']['special'] ?? $this->defaultFilters['special']),
            'developerType' => $validatedData['filter']['developerType'] ?? $this->defaultFilters['developerType'],
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

    public function getSorts(bool $withDeveloper = true, bool $withExpiring = true): array
    {
        $sorts['title'] = 'Game Title';

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
            'render' => function ($claim) {
                echo Blade::render('
                    <x-game.multiline-avatar
                        :gameId="$ID"
                        :gameTitle="$Title"
                        :gameImageIcon="$ImageIcon"
                        :consoleName="$consoleName"
                    />', [
                        'ID' => $claim->game->id,
                        'Title' => $claim->game->title,
                        'ImageIcon' => $claim->game->ImageIcon,
                        'consoleName' => $claim->game->system->name,
                    ]);
            },
        ];
    }

    public function getDeveloperColumn(): array
    {
        return [
            'header' => 'Developer',
            'render' => function ($claim) {
                echo userAvatar($claim->user);
            },
        ];
    }

    public function getClaimTypeColumn(): array
    {
        return [
            'header' => 'Claim Type',
            'render' => function ($claim) {
                echo ClaimType::toString($claim->ClaimType);
            },
        ];
    }

    public function getSetTypeColumn(): array
    {
        return [
            'header' => 'Set Type',
            'render' => function ($claim) {
                echo ClaimSetType::toString($claim->SetType);
            },
        ];
    }

    public function getStatusColumn(): array
    {
        return [
            'header' => 'Status',
            'render' => function ($claim) {
                echo ClaimStatus::toString($claim->Status);
            },
        ];
    }

    public function getSpecialColumn(): array
    {
        return [
            'header' => 'Special',
            'render' => function ($claim) {
                echo ClaimSpecial::toString($claim->Special);
            },
        ];
    }

    private function renderDate(?Carbon $date): void
    {
        echo '<span class="smalldate whitespace-nowrap">';
        if ($date) {
            echo getNiceDate($date->unix());
        } else {
            echo 'Unknown';
        }
        echo '</span>';
    }

    public function getClaimDateColumn(): array
    {
        return [
            'header' => 'Claimed At',
            'render' => function ($claim) {
                $this->renderDate($claim->Created);
            },
        ];
    }

    public function getEndDateColumn(): array
    {
        return [
            'header' => 'Expires/Finished At',
            'render' => function ($claim) {
                $this->renderDate($claim->Finished);
            },
        ];
    }

    public function getFinishedDateColumn(): array
    {
        return [
            'header' => 'Finished At',
            'render' => function ($claim) {
                $this->renderDate($claim->Finished);
            },
        ];
    }

    public function getExpirationDateColumn(): array
    {
        return [
            'header' => 'Expires At',
            'render' => function ($claim) {
                $this->renderDate($claim->Finished);
            },
        ];
    }

    public function getExpirationStatusColumn(): array
    {
        return [
            'header' => 'Expiration Status',
            'render' => function ($claim) {
                if (ClaimStatus::isActive($claim->Status)) {
                    $now = Carbon::now();
                    if ($claim->Finished < $now) {
                        echo '<span class="text-danger">';
                    }

                    echo $claim->Finished->diffForHumans($now, ['syntax' => Carbon::DIFF_RELATIVE_TO_NOW]);

                    if ($claim->Finished < $now) {
                        echo '</span>';
                    }
                }
            },
        ];
    }
}
