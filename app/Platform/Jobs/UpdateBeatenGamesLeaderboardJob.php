<?php

declare(strict_types=1);

namespace App\Platform\Jobs;

use App\Models\System;
use App\Platform\Actions\UpdateBeatenGamesLeaderboardAction;
use App\Platform\Enums\PlayerStatRankingKind;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateBeatenGamesLeaderboardJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly ?int $systemId,
        private readonly PlayerStatRankingKind $kind,
    ) {
    }

    public int $uniqueFor = 600; // 10 minutes.

    public function uniqueId(): string
    {
        if (config('queue.default') === 'sync') {
            return '';
        }

        $systemKey = $this->systemId ?? 'overall';

        return "beaten-leaderboard-{$systemKey}-{$this->kind->value}";
    }

    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        $systemKey = $this->systemId ?? 'overall';

        return [
            'beaten-games-leaderboard',
            "system:{$systemKey}",
            "kind:{$this->kind->value}",
        ];
    }

    public function handle(): void
    {
        if ($this->systemId !== null) {
            $system = System::find($this->systemId);
            if (!$system) {
                return;
            }
        }

        app()->make(UpdateBeatenGamesLeaderboardAction::class)
            ->execute($this->systemId, $this->kind);
    }
}
