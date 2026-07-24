<?php

declare(strict_types=1);

namespace App\Platform\Jobs;

use App\Platform\Actions\UpdatePlayerGlobalRankingsAction;
use App\Platform\Enums\GlobalRankingWindow;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdatePlayerGlobalRankingsJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 30, 60];
    public int $uniqueFor = 900;

    public function __construct(private readonly GlobalRankingWindow $window)
    {
    }

    public function uniqueId(): string
    {
        return "player-global-rankings-{$this->window->value}";
    }

    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'player-global-rankings',
            "window:{$this->window->value}",
        ];
    }

    public function handle(UpdatePlayerGlobalRankingsAction $action): void
    {
        $action->execute($this->window);
    }
}
