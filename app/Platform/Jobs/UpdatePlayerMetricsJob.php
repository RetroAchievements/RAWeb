<?php

namespace App\Platform\Jobs;

use App\Platform\Actions\UpdatePlayerMetrics;
use App\Site\Models\User;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;

class UpdatePlayerMetricsJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
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
        try {
            app()->make(UpdatePlayerMetrics::class)
                ->execute(User::findOrFail($this->userId));
        } catch (Exception $exception) {
            Log::error($exception->getMessage(), ['exception' => $exception]);
            $this->fail($exception);
        }
    }
}
