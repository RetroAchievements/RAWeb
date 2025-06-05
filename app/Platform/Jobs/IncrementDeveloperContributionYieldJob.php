<?php

declare(strict_types=1);

namespace App\Platform\Jobs;

use App\Models\Achievement;
use App\Models\PlayerAchievement;
use App\Models\User;
use App\Platform\Actions\IncrementDeveloperContributionYieldAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class IncrementDeveloperContributionYieldJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly int $developerId,
        private readonly int $achievementId,
        private readonly int $playerAchievementId,
        private readonly bool $isUnlock,
    ) {
    }

    public int $uniqueFor = 3600;

    public function uniqueId(): string
    {
        return config('queue.default') === 'sync'
            ? ''
            : "{$this->developerId}-{$this->achievementId}-{$this->playerAchievementId}-{$this->isUnlock}";
    }

    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            User::class . ':' . $this->developerId,
            Achievement::class . ':' . $this->achievementId,
            PlayerAchievement::class . ':' . $this->playerAchievementId,
        ];
    }

    public function handle(): void
    {
        $developer = User::withTrashed()->find($this->developerId);
        if (!$developer) {
            return;
        }

        $achievement = Achievement::find($this->achievementId);
        if (!$achievement) {
            return;
        }

        $playerAchievement = PlayerAchievement::find($this->playerAchievementId);
        if (!$playerAchievement) {
            return;
        }

        app()->make(IncrementDeveloperContributionYieldAction::class)->execute(
            $developer,
            $achievement,
            $playerAchievement,
            $this->isUnlock
        );
    }
}
