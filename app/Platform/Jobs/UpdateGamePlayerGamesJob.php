<?php

namespace App\Platform\Jobs;

use App\Models\Game;
use App\Platform\Actions\UpdateGamePlayerGamesAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\DebounceFor;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Force this to run 5 minutes after the last change to a game's set.
 * This forces any burst of changes to be evaluated once in its final state.
 * Otherwise, a set that is fully demoted and then repromoted one achievement at
 * a time can grant mastery badges for the partly-promoted set.
 */
#[DebounceFor(300, maxWait: 3600)]
class UpdateGamePlayerGamesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly int $gameId,
    ) {
    }

    public function debounceId(): string
    {
        return (string) $this->gameId;
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
        app()->make(UpdateGamePlayerGamesAction::class)
            ->execute(Game::findOrFail($this->gameId));
    }
}
