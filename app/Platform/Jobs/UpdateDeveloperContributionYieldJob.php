<?php

namespace App\Platform\Jobs;

use App\Platform\Actions\UpdateDeveloperContributionYield;
use App\Site\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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

    public function handle(): void
    {
        app()->make(UpdateDeveloperContributionYield::class)
            ->execute(User::findOrFail($this->userId));
    }
}
