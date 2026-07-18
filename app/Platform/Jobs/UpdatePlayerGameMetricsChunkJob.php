<?php

declare(strict_types=1);

namespace App\Platform\Jobs;

use App\Models\Game;
use App\Platform\Actions\UpdatePlayerGameMetricsChunkAction;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdatePlayerGameMetricsChunkJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  list<int>  $userIds
     */
    public function __construct(
        private readonly int $gameId,
        private readonly ?string $expectedVersionHash,
        private readonly array $userIds,
    ) {
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
        if ($this->batch()?->cancelled()) {
            return;
        }

        // If the hash changed before we even start processing the chunk, bail.
        // If it changes mid-chunk, it'll get caught by the `finally()` in UpdateGamePlayerGamesAction.
        if ($this->expectedVersionHash !== null) {
            $currentHash = Game::where('id', $this->gameId)
                ->value('achievement_set_version_hash');

            if ($currentHash !== $this->expectedVersionHash) {
                $this->batch()?->cancel();

                return;
            }
        }

        app()->make(UpdatePlayerGameMetricsChunkAction::class)
            ->execute($this->gameId, $this->userIds);
    }
}
