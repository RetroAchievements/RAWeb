<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Community\Enums\AwardType;
use App\Models\PlayerBadge;
use Illuminate\Database\Eloquent\Builder;

class AwardEarnersService
{
    private AwardType $awardType;
    private int $awardId;
    private int $awardExtra;

    public function initialize(AwardType $type, int $id, int $extra): void
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
            ->where('award_type', $this->awardType)
            ->where('award_data', $this->awardId)
            ->where('award_data_extra', $this->awardExtra)
            ->whereNotExists(function ($query) {
                $query->select('user_id')
                    ->from('unranked_users')
                    ->whereColumn('unranked_users.user_id', 'user_awards.user_id');
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
            ->orderBy('awarded_at');
    }
}
