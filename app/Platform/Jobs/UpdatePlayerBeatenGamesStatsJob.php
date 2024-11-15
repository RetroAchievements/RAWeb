<?php

declare(strict_types=1);

namespace App\Platform\Jobs;

use App\Models\User;
use App\Platform\Actions\UpdatePlayerBeatenGamesStatsAction;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdatePlayerBeatenGamesStatsJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly int $userId,
    ) {
    }

    public int $uniqueFor = 3600;

    public function uniqueId(): string
    {
        return config('queue.default') === 'sync' ? '' : "{$this->userId}";
    }

    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            User::class . ':' . $this->userId,
        ];
    }

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $user = User::find($this->userId);
        if (!$user) {
            // might've been deleted
            return;
        }

        app()->make(UpdatePlayerBeatenGamesStatsAction::class)
            ->execute($user);
    }
}
