<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Models\PlayerBadge;
use Illuminate\Database\Eloquent\Builder;

class AwardEarnersService
{
    private int $awardType;
    private int $awardId;
    private int $awardExtra;

    public function initialize(int $type, int $id, int $extra): void
    {
        $this->awardType = $type;
        $this->awardId = $id;
        $this->awardExtra = $extra;
    }

    /**
     * @return Builder<PlayerBadge>
     */
    private function baseQuery(): Builder
    {
        return PlayerBadge::query()
            ->where('AwardType', $this->awardType)
            ->where('AwardData', $this->awardId)
            ->where('AwardDataExtra', $this->awardExtra)
            ->whereNotExists(function ($query) {
                $query->select('user_id')
                    ->from('unranked_users')
                    ->whereColumn('unranked_users.user_id', 'SiteAwards.user_id');
            });
    }

    public function numEarners(): int
    {
        return $this->baseQuery()->count();
    }

    /**
     * @return Builder<PlayerBadge>
     */
    public function allEarners(): Builder
    {
        return $this->baseQuery()->with('user')
            ->orderBy('AwardDate');
    }
}
