<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Models\Game;
use App\Models\PlayerBadge;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

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
     * @return Builder<PlayerGame>
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
     * @return Collection<int, PlayerGame>
     */
    public function allEarners(int $offset = 0, int $count = 10): Builder
    {
        return $this->baseQuery()->with('user')
            ->orderBy('AwardDate')
            ->offset($offset)
            ->limit($count);
    }
}
