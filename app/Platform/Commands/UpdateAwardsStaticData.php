<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Community\Enums\AwardType;
use App\Models\PlayerBadge;
use App\Models\StaticData;
use App\Platform\Enums\UnlockMode;
use Illuminate\Console\Command;

/**
 * @deprecated use a query in the component instead - most "last of something" queries are not expensive
 */
class UpdateAwardsStaticData extends Command
{
    protected $signature = 'ra:platform:static:update-awards-data';

    protected $description = 'Update mastery and beaten awards statistics in the StaticData table';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->updateHardcoreMasteryAwardsCount();
        $this->updateHardcoreGameBeatenAwardsCount();
    }

    private function updateHardcoreMasteryAwardsCount(): void
    {
        $masteryAwardsCount = PlayerBadge::query()
            ->leftJoin('unranked_users', 'user_awards.user_id', '=', 'unranked_users.user_id')
            ->whereNull('unranked_users.user_id')
            ->where('award_type', AwardType::Mastery)
            ->where('award_tier', UnlockMode::Hardcore)
            ->count();

        StaticData::query()->update(['num_hardcore_mastery_awards' => $masteryAwardsCount]);
    }

    private function updateHardcoreGameBeatenAwardsCount(): void
    {
        $hardcoreGameBeatenAwardsCount = PlayerBadge::query()
            ->leftJoin('unranked_users', 'user_awards.user_id', '=', 'unranked_users.user_id')
            ->whereNull('unranked_users.user_id')
            ->where('award_type', AwardType::GameBeaten)
            ->where('award_tier', UnlockMode::Hardcore)
            ->count();

        StaticData::query()->update(['num_hardcore_game_beaten_awards' => $hardcoreGameBeatenAwardsCount]);
    }
}
