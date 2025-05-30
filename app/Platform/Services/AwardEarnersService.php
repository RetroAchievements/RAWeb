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
            ->whereHas('user', function ($query) {
                return $query->tracked();
            });
    }

    public function numEarners(): int
    {
        return $this->baseQuery()->count();
    }

    /**
     * @return Builder<PlayerBadge>
     */
    public function allEarners(int $offset = 0, int $count = 10): Builder
    {
        return $this->baseQuery()->with('user')
            ->orderBy('AwardDate')
            ->offset($offset)
            ->limit($count);
    }
}
