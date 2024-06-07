<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Platform\Actions\UpdateTotalGamesCount as UpdateTotalGamesCountAction;
use Illuminate\Console\Command;

class UpdateTotalGamesCount extends Command
{
    protected $signature = 'ra:platform:static:update-total-games-count';
    protected $description = 'Update the tracked count of total unique games';

    public function __construct(
        private readonly UpdateTotalGamesCountAction $updateTotalGamesCount
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->updateTotalGamesCount->execute();
    }
}
