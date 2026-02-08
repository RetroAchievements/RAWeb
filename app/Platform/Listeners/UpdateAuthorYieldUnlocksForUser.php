<?php

declare(strict_types=1);

namespace App\Platform\Listeners;

use App\Platform\Actions\UpdateAuthorYieldUnlocksForUserAction;
use App\Platform\Events\PlayerRankedStatusChanged;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateAuthorYieldUnlocksForUser implements ShouldQueue
{
    public function handle(PlayerRankedStatusChanged $event): void
    {
        app()->make(UpdateAuthorYieldUnlocksForUserAction::class)
            ->execute($event->user);
    }
}
