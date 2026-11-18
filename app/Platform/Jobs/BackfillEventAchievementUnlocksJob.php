<?php

declare(strict_types=1);

namespace App\Platform\Jobs;

use App\Models\EventAchievement;
use App\Platform\Actions\BackfillEventAchievementUnlocksAction;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class BackfillEventAchievementUnlocksJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $maxExceptions = 3; // permit a few real failures while the retry window handles contention
    public array $backoff = [30, 120]; // give transient DB contention time to clear

    public function __construct(
        private readonly int $eventAchievementId,
        private readonly int $eventGameId,
        private readonly ?int $afterId = null, // cursor, player_achievements.id of the last source row processed
    ) {
    }

    public function retryUntil(): DateTimeInterface
    {
        // Use a time-based lock rather than a retry-count lock.
        // With a time-based lock, jobs will fail just from timeouts when a
        // flood of jobs arrives.
        return now()->addHours(4);
    }

    /**
     * @return array<int, WithoutOverlapping>
     */
    public function middleware(): array
    {
        return [
            // Release blocked continuations after 10 seconds. Recover stuck locks after
            // 15 minutes in case a worker exists before the middleware can release it.
            (new WithoutOverlapping((string) $this->eventGameId))
                ->releaseAfter(10)
                ->expireAfter(900),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            EventAchievement::class . ':' . $this->eventAchievementId,
            'event-game:' . $this->eventGameId,
        ];
    }

    public function handle(): void
    {
        // Chunks are kept independent. A failed chunk will retry its own writes and recompute,
        // and the action returns a cursor only when more winners remain. The final chunk
        // will fire aggregate updates, while every continuation stays on the isolated queue.
        $nextAfterId = app()->make(BackfillEventAchievementUnlocksAction::class)
            ->execute($this->eventAchievementId, $this->afterId);

        if ($nextAfterId !== null) {
            dispatch(new self($this->eventAchievementId, $this->eventGameId, $nextAfterId))
                ->onQueue($this->queue);
        }
    }
}
