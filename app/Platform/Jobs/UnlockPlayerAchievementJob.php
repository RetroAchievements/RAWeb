<?php

namespace App\Platform\Jobs;

use App\Models\Achievement;
use App\Models\GameHash;
use App\Models\User;
use App\Platform\Actions\UnlockPlayerAchievementAction;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UnlockPlayerAchievementJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly int $userId,
        private readonly int $achievementId,
        private readonly bool $hardcore,
        private ?Carbon $timestamp = null,
        private readonly ?int $unlockedByUserId = null,
        private readonly ?int $gameHashId = null,
    ) {
        $this->timestamp ??= Carbon::now();
    }

    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            User::class . ':' . $this->userId,
            Achievement::class . ':' . $this->achievementId,
            'unlock:' . ($this->unlockedByUserId ? 'manual' : 'organic'),
        ];
    }

    public function handle(): void
    {
        app()->make(UnlockPlayerAchievementAction::class)->execute(
            User::findOrFail($this->userId),
            Achievement::findOrFail($this->achievementId),
            $this->hardcore,
            $this->timestamp,
            $this->unlockedByUserId ? User::findOrFail($this->unlockedByUserId) : null,
            $this->gameHashId ? GameHash::find($this->gameHashId) : null,
        );
    }
}
