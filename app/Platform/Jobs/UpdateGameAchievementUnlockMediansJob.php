<?php

namespace App\Platform\Jobs;

use App\Models\Game;
use App\Platform\Actions\UpdateGameAchievementUnlockMediansAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateGameAchievementUnlockMediansJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly int $gameId,
    ) {
    }

    public int $uniqueFor = 86400; // 1 day

    public function uniqueId(): string
    {
        return config('queue.default') === 'sync' ? '' : $this->gameId;
    }

    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            Game::class . ':' . $this->gameId,
        ];
    }

    public function handle(): void
    {
        app()->make(UpdateGameAchievementUnlockMediansAction::class)
            ->execute(Game::findOrFail($this->gameId));
    }
}
