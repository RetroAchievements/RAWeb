<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Community\Enums\AwardType;
use App\Platform\Enums\UnlockMode;
use App\Platform\Models\PlayerBadge;
use App\Site\Models\StaticData;
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
        $this->updateLastGameHardcoreMastered();
        $this->updateLastGameHardcoreBeaten();
    }

    private function updateHardcoreMasteryAwardsCount(): void
    {
        $masteryAwardsCount = PlayerBadge::with('user')
            ->whereHas('user', function ($query) {
                $query->where('Untracked', 0);
            })
            ->where('AwardType', AwardType::Mastery)
            ->where('AwardDataExtra', UnlockMode::Hardcore)
            ->count();

        StaticData::query()->update(['num_hardcore_mastery_awards' => $masteryAwardsCount]);
    }

    private function updateHardcoreGameBeatenAwardsCount(): void
    {
        $hardcoreGameBeatenAwardsCount = PlayerBadge::with('user')
            ->whereHas('user', function ($query) {
                $query->where('Untracked', 0);
            })
            ->where('AwardType', AwardType::GameBeaten)
            ->where('AwardDataExtra', UnlockMode::Hardcore)
            ->count();

        StaticData::query()->update(['num_hardcore_game_beaten_awards' => $hardcoreGameBeatenAwardsCount]);
    }

    private function updateLastGameHardcoreMastered(): void
    {
        $foundAward = PlayerBadge::with('user')
            ->whereHas('user', function ($query) {
                $query->where('Untracked', 0);
            })
            ->where('AwardType', AwardType::Mastery)
            ->where('AwardDataExtra', UnlockMode::Hardcore)
            ->orderByDesc('AwardDate')
            ->first(['AwardData', 'AwardDate', 'User']);

        if ($foundAward) {
            StaticData::query()->update([
                'last_game_hardcore_mastered_game_id' => $foundAward->AwardData,
                'last_game_hardcore_mastered_user_id' => $foundAward->user->ID,
                'last_game_hardcore_mastered_at' => $foundAward->AwardDate,
            ]);
        }
    }

    private function updateLastGameHardcoreBeaten(): void
    {
        $foundAward = PlayerBadge::with('user')
            ->whereHas('user', function ($query) {
                $query->where('Untracked', 0);
            })
            ->where('AwardType', AwardType::GameBeaten)
            ->where('AwardDataExtra', UnlockMode::Hardcore)
            ->orderByDesc('AwardDate')
            ->first(['AwardData', 'AwardDate', 'User']);

        if ($foundAward) {
            StaticData::query()->update([
                'last_game_hardcore_beaten_game_id' => $foundAward->AwardData,
                'last_game_hardcore_beaten_user_id' => $foundAward->user->ID,
                'last_game_hardcore_beaten_at' => $foundAward->AwardDate,
            ]);
        }
    }
}
