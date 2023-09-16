<?php

namespace App\Platform\Jobs;

use App\Platform\Actions\UnlockPlayerAchievementAction;
use App\Platform\Models\Achievement;
use App\Site\Models\User;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Log;

class UnlockPlayerAchievementJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly int $userId,
        private readonly int $achievementId,
        private readonly bool $hardcore,
        private ?Carbon $timestamp = null,
        private readonly ?int $unlockedByUserId = null,
    ) {
        $this->timestamp ??= Carbon::now();
    }

    public function uniqueId(): string
    {
        return $this->userId . '-' . $this->achievementId . '-' . $this->hardcore;
    }

    public function handle(): void
    {
        try {
            app()->make(UnlockPlayerAchievementAction::class)->execute(
                User::findOrFail($this->userId),
                Achievement::findOrFail($this->achievementId),
                $this->hardcore,
                $this->timestamp,
                $this->unlockedByUserId ? User::findOrFail($this->unlockedByUserId) : null,
            );
        } catch (Exception $exception) {
            // Log::error($exception->getMessage(), ['exception' => $exception]);
            $this->fail($exception);
        }
    }
}
