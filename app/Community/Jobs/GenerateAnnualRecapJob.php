<?php

namespace App\Community\Jobs;

use App\Community\Actions\GenerateAnnualRecapAction;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateAnnualRecapJob implements ShouldQueue
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
        $user = User::find($this->userId);

        if (!$user) {
            return;
        }

        app()->make(GenerateAnnualRecapAction::class)
            ->execute($user);
    }
}
