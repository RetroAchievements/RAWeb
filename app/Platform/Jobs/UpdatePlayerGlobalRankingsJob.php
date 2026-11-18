<?php

declare(strict_types=1);

namespace App\Platform\Jobs;

use App\Platform\Actions\UpdatePlayerGlobalRankingsAction;
use App\Platform\Enums\GlobalRankingWindow;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class UpdatePlayerGlobalRankingsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;
    public int $timeout = 3000;
    public bool $failOnTimeout = true;
    public int $uniqueFor = 3600;

    public function __construct()
    {
        $this->onQueue('player-global-rankings');
    }

    /**
     * @return array<int, WithoutOverlapping>
     */
    public function middleware(): array
    {
        // Force new jobs to respect locks held by older workers.
        // Concurrent rebuilds can deadlock.
        return [
            (new WithoutOverlapping('player-global-rankings-update'))
                ->shared()
                ->withPrefix('')
                ->dontRelease()
                ->expireAfter(3600),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        return ['player-global-rankings'];
    }

    public function handle(UpdatePlayerGlobalRankingsAction $action): void
    {
        // Keep recent rankings fresh even if the slower all-time rebuild fails.
        $action->execute(GlobalRankingWindow::Daily);
        $action->execute(GlobalRankingWindow::Weekly);
        $action->execute(GlobalRankingWindow::AllTime);
    }
}
