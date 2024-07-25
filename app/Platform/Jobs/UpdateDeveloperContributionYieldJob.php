<?php

namespace App\Platform\Jobs;

use App\Models\User;
use App\Platform\Actions\UpdateDeveloperContributionYield;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateDeveloperContributionYieldJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
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
        return config('queue.default') === 'sync' ? '' : $this->userId;
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
        $user = User::find($this->userId);

        // This often occurs if the user has deleted their account.
        if (!$user) {
            Log::info("User [{$this->userId}] not found for UpdateDeveloperContributionYield.");

            return;
        }

        app()->make(UpdateDeveloperContributionYield::class)
            ->execute($user);
    }
}
