<?php

namespace App\Platform\Jobs;

use App\Models\Achievement;
use App\Platform\Actions\UpdateDemotedAchievementPlayerMetricsAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateDemotedAchievementPlayerMetricsJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly int $achievementId,
    ) {
    }

    public int $uniqueFor = 3600;

    public function uniqueId(): string
    {
        return config('queue.default') === 'sync' ? '' : $this->achievementId;
    }

    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            Achievement::class . ':' . $this->achievementId,
        ];
    }

    public function handle(): void
    {
        app(UpdateDemotedAchievementPlayerMetricsAction::class)
            ->execute(Achievement::findOrFail($this->achievementId));
    }
}
