<?php

declare(strict_types=1);

namespace App\Platform\Jobs;

use App\Platform\Actions\UpdatePlayerPointsStatsBatchAction;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class UpdatePlayerPointsStatsBatchJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param array<int> $userIds
     */
    public function __construct(
        private readonly array $userIds,
        private readonly ?string $mockCurrentDate = null, // for testing only
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'player-points-stats-batch:' . count($this->userIds),
        ];
    }

    public function handle(): void
    {
        if ($this->mockCurrentDate) {
            Carbon::setTestNow(Carbon::parse($this->mockCurrentDate));
        }

        app()->make(UpdatePlayerPointsStatsBatchAction::class)
            ->execute($this->userIds);
    }
}
