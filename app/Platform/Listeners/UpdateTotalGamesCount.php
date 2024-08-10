<?php

declare(strict_types=1);

namespace App\Platform\Listeners;

use App\Platform\Actions\UpdateTotalGamesCount as UpdateTotalGamesCountAction;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateTotalGamesCount implements ShouldQueue
{
    public function handle(): void
    {
        app()->make(UpdateTotalGamesCountAction::class)->execute();
    }
}
